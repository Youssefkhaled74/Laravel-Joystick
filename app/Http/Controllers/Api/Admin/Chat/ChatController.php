<?php

namespace App\Http\Controllers\Api\Admin\Chat;

use Log;
use App\Models\Chat;
use App\Models\User;
use App\Models\Question;
use App\Traits\ApiResponse;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Service\FirebaseService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Exception\DatabaseException;

class ChatController extends Controller
{
    use ApiResponse;
    protected $firebaseService;
    protected $chatPath = 'chat';

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $user = Auth::guard('api')->user();
        if (!$user || $user->role !== 'user') {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        Conversation::where('user_id', $user->id)
            ->where('status', 'open')
            ->where('customer_service_id', '!=', 1)
            ->update(['customer_service_id' => 1]);

        $chatbotReply = $this->getChatbotReply($request->input('message'));

        if ($chatbotReply) {
            $conversation = Conversation::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'customer_service_id' => 1, // Chatbot ID
                ],
                [
                    'status' => 'open',
                ]
            );

            $userMessageData = [
                'user_id'         => $user->id,
                'conversation_id' => $conversation->id,
                'username'        => $user->username,
                'role'            => 'user',
                'message'         => $request->input('message'),
                'created_at'      => now()->toDateTimeString(),
                'reply'           => false,
                'seen'            => false,
            ];
            $chatUser = Chat::create($userMessageData);

            $chatbotMessageData = [
                'user_id'         => 1, // Chatbot ID
                'conversation_id' => $conversation->id,
                'username'        => 'Chatbot',
                'role'            => 'customer_service',
                'message'         => $chatbotReply,
                'created_at'      => now()->toDateTimeString(),
                'reply'           => true,
                'seen'            => true,
            ];
            $chatBot = Chat::create($chatbotMessageData);

            $this->firebaseService->sendMessage($conversation->id, $userMessageData);
            $this->firebaseService->sendMessage($conversation->id, $chatbotMessageData);

            return $this->successResponse(200, __('messages.message_sent'), [
                'chatbot_reply'   => $chatbotReply,
                'conversation_id' => $conversation->id,
            ]);
        }

        $conversation = Conversation::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$conversation) {
            $customerServices = User::where('role', 'customer_service')
                ->withCount(['assignedConversations' => function ($query) {
                    $query->where('status', 'open');
                }])
                ->orderBy('assigned_conversations_count', 'asc')
                ->get();

            if ($customerServices->isEmpty()) {
                return $this->errorResponse(500, __('messages.no_customer_service_available'));
            }

            $customerService = $customerServices->firstWhere(function ($cs) {
                return $cs->assigned_conversations_count < 5;
            }) ?? $customerServices->first();

            $conversation = Conversation::create([
                'user_id'             => $user->id,
                'customer_service_id' => $customerService->id,
                'status'              => 'open',
            ]);
        }

        $chatData = [
            'user_id'         => $user->id,
            'conversation_id' => $conversation->id,
            'username'        => $user->username,
            'role'            => $user->role,
            'message'         => $request->input('message'),
            'created_at'      => now()->toDateTimeString(),
            'reply'           => false,
            'seen'            => false,
        ];

        $chat = Chat::create($chatData);

        $this->firebaseService->sendMessage($conversation->id, $chatData);

        return $this->successResponse(200, __('messages.message_sent'), $chatData);
    }
    public function replyMessage(Request $request)
    {
        $request->validate([
            'message'         => 'required|string|max:1000',
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $user = Auth::guard('api')->user();
        if (!$user || $user->role !== 'customer_service') {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $conversation = Conversation::findOrFail($request->conversation_id);

        if ($conversation->customer_service_id == 1) {
            $conversation->update(['customer_service_id' => $user->id]);
        }

        $data = [
            'user_id'         => $user->id,
            'conversation_id' => $conversation->id,
            'username'        => $user->username,
            'role'            => 'customer_service',
            'message'         => $request->input('message'),
            'created_at'      => now()->toDateTimeString(),
            'reply'           => true,
            'seen'            => true,
        ];

        $chatReply = Chat::create($data);

        try {
            $this->firebaseService->sendMessage($conversation->id, $data);
        } catch (\Exception $e) {
            return $this->errorResponse(500, $e->getMessage());
        }
        return $this->successResponse(200, __('messages.message_sent'), $chatReply);
    }
    public function getMessages(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
    
        $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
        ]);
    
        $conversationId = $request->conversation_id;
    
        if ($user->role === 'user') {
            $conversation = Conversation::where('id', $conversationId)
                ->where('user_id', $user->id)
                ->first();
        } elseif ($user->role === 'customer_service') {
            $conversation = Conversation::where('id', $conversationId)
                ->where(function ($query) use ($user) {
                    $query->where('customer_service_id', $user->id)
                          ->orWhere('customer_service_id', 1);
                })
                ->first();
        } else {
            return $this->errorResponse(403, __('messages.unauthorized'));
        }
    
        if (!$conversation) {
            return $this->errorResponse(404, __('messages.conversation_not_found'));
        }
    
        Chat::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->where('seen', false)
            ->update(['seen' => true]);
    
        $messages = Chat::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->get();
    
        return $this->successResponse(200, __('messages.messages_retrieved'), $messages);
    }
    public function listConversations(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user || $user->role !== 'customer_service') {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $conversations = Conversation::whereIn('customer_service_id', [$user->id, 1])
            ->where('status', 'open')
            ->with('user:id,username')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(200, __('messages.conversations_retrieved'), $conversations);
    }
    public function getUserSuggestion()
    {
        $suggest = [
            'اهم خدمات Joystick Repair',
            'أزاى بنقدم خدمة Hall Effect',
            'ايه هى خدمة ال Customize اللى Joystick Repair بتقدمها لل controller',
            'ايه هو Water Print Cover',
            'ازاى بنقدم خدماتنا فى منزلك',
            'ازاى بنقدم خدماتنا للإستخدام التجارى (البلايستيشن كافيه)',
            'ليه Joystick Repair معندهاش ستور تبيع أجهزة وكونترولر واكسسوارتها',
        ];
        try {
            return $this->successResponse(200, __('messages.suggestions'), $suggest);
        } catch (\Exception $e) {
            return $this->errorResponse(500, $e->getMessage());
        }
    }
    private function getChatbotReply($message)
    {
        if ($response = $this->handleCasualConversation($message)) {
            return $response;
        }

        $question = Question::where('question', 'LIKE', "%{$message}%")->first();

        return $question ? $question->answer : $this->getFallbackResponse();
    }
    private function handleCasualConversation($message)
    {
        $casualPhrases = [
            'greetings' => [
                // Arabic
                'صباح الخير' => 'صباح النور! كيف يمكنني مساعدتك اليوم؟',
                'صباح خير' => 'صباح النور! كيف يمكنني مساعدتك اليوم؟',
                'صبح الخير' => 'صباح النور! كيف يمكنني مساعدتك اليوم؟',
                'صباح النور' => 'صباح الخير! هل لديك أي استفسار؟',
                'صبح النور' => 'صباح الخير! هل لديك أي استفسار؟',
                'اهلا' => 'أهلاً وسهلاً! كيف يمكنني مساعدتك؟',
                'اهلاً' => 'أهلاً وسهلاً! كيف يمكنني مساعدتك؟',
                'اهلا وسهلا' => 'أهلاً وسهلاً! كيف يمكنني مساعدتك؟',
                'هلا' => 'هلا بيك! هل تريد حجز موعد صيانة؟',
                'هلا والله' => 'هلا بيك! هل تريد حجز موعد صيانة؟',
                'مرحبا' => 'مرحباً! كيف يمكنني مساعدتك؟',
                'مرحبتين' => 'مرحباً! كيف يمكنني مساعدتك؟',
                'سلام' => 'سلام! كيف أقدر أساعدك؟',
                'السلام عليكم' => 'وعليكم السلام! كيف يمكنني مساعدتك؟',
                'سلام عليكم' => 'وعليكم السلام! كيف يمكنني مساعدتك؟',
                'هاي' => 'هاي! هل تحتاج مساعدة؟',
                'صباح الفل' => 'صباح الورد! كيف أقدر أساعدك؟',
                'صباح الورد' => 'صباح الفل! كيف أقدر أساعدك؟',
    
                // English
                'good morning' => 'Good morning! How can I assist you today?',
                'gm' => 'Good morning! How can I assist you today?',
                'good afternoon' => 'Good afternoon! How may I help you?',
                'ga' => 'Good afternoon! How may I help you?',
                'good evening' => 'Good evening! Do you have any questions?',
                'ge' => 'Good evening! Do you have any questions?',
                'hello' => 'Hello! How can I assist you?',
                'hllo' => 'Hello! How can I assist you?',
                'hi' => 'Hi! What can I do for you?',
                'hey' => 'Hey! How can I help?',
                'heyy' => 'Hey! How can I help?',
                'howdy' => 'Howdy! What brings you here?',
                'greetings' => 'Greetings! How can I assist you today?',
    
                // Franco
                'sabah el kheer' => 'Sabah el noor! How can I help you today?',
                'sabah el kher' => 'Sabah el noor! How can I help you today?',
                'sabah elkheer' => 'Sabah el noor! How can I help you today?',
                'sabah elkher' => 'Sabah el noor! How can I help you today?',
                'sbah el kheer' => 'Sabah el noor! How can I help you today?',
                'sbah el kher' => 'Sabah el noor! How can I help you today?',
                'sb7 el kheer' => 'Sabah el noor! How can I help you today?',
                'sab7 el kheer' => 'Sabah el noor! How can I help you today?',
                'masa el kheer' => 'Masa el noor! Do you have any questions?',
                'masa el kher' => 'Masa el noor! Do you have any questions?',
                'ahlan' => 'Ahlan! How can I assist you?',
                'ahlan bik' => 'Ahlan! How can I assist you?',
                'marhaba' => 'Marhaba! What can I do for you?',
                'marhabtain' => 'Marhaba! What can I do for you?',
                'salam' => 'Salam! How can I help?',
                'salaam' => 'Salam! How can I help?',
            ],
            'farewell' => [
                // Arabic
                'مع السلامة' => 'مع السلامة! لا تتردد في التواصل إذا احتجت أي مساعدة',
                'مع السلامه' => 'مع السلامة! لا تتردد في التواصل إذا احتجت أي مساعدة',
                'باي' => 'وداعاً! أتمنى يكون يومك كويس',
                'بااي' => 'وداعاً! أتمنى يكون يومك كويس',
                'سلام' => 'سلام! إذا احتجت أي شيء، أنا هنا',
                'سلام عليكم' => 'سلام! إذا احتجت أي شيء، أنا هنا',
                'في أمان الله' => 'في أمان الله! تواصل معنا إذا احتجت مساعدة',
                'وداعا' => 'وداعاً! أتمنى لك يومًا سعيدًا',
                'وداعاً' => 'وداعاً! أتمنى لك يومًا سعيدًا',
    
                // English
                'goodbye' => 'Goodbye! Have a great day!',
                'gb' => 'Goodbye! Have a great day!',
                'bye' => 'Bye! Don\'t hesitate to reach out if you need help',
                'byee' => 'Bye! Don\'t hesitate to reach out if you need help',
                'see you' => 'See you later! Contact us anytime',
                'cya' => 'See you later! Contact us anytime',
                'take care' => 'Take care! Let us know if you need anything',
                'ttyl' => 'Take care! Let us know if you need anything',
                'farewell' => 'Farewell! Have a wonderful day',
    
                // Franco
                'ma3a el salama' => 'Ma3a el salama! Let us know if you need anything',
                'ma3 salama' => 'Ma3a el salama! Let us know if you need anything',
                'bye ya basha' => 'Bye ya basha! Take care',
                'salam' => 'Salam! Hit us up if you need help',
                'salaam' => 'Salam! Hit us up if you need help',
            ],
            'thanks' => [
                // Arabic
                'شكرا' => 'العفو! دا واجبنا. هل تحتاج أي حاجة تانية؟',
                'شكراً' => 'العفو! دا واجبنا. هل تحتاج أي حاجة تانية؟',
                'شكر' => 'العفو! دا واجبنا. هل تحتاج أي حاجة تانية؟',
                'متشكر' => 'لا شكر على واجب! تكرم عينك',
                'متشكرة' => 'لا شكر على واجب! تكرم عينك',
                'شكراً جزيلاً' => 'العفو! دا واجبنا. هل تحتاج أي مساعدة أخرى؟',
                'شكرا جزيلا' => 'العفو! دا واجبنا. هل تحتاج أي مساعدة أخرى؟',
                'ميرسي' => 'ميرسي! دا واجبنا. هل تحتاج أي حاجة تانية؟',
                'تسلم' => 'تسلم! دا واجبنا. هل تحتاج أي مساعدة؟',
                'تسلمي' => 'تسلم! دا واجبنا. هل تحتاج أي مساعدة؟',
    
                // English
                'thank you' => 'You\'re welcome! Is there anything else I can help with?',
                'thx' => 'You\'re welcome! Is there anything else I can help with?',
                'thanks' => 'My pleasure! Let me know if you need further assistance',
                'thank you so much' => 'You\'re very welcome! How else can I assist you?',
                'ty' => 'You\'re very welcome! How else can I assist you?',
                'appreciate it' => 'Anytime! Let me know if you need anything else',
                'cheers' => 'Cheers! Happy to help. What else can I do for you?',
    
                // Franco
                'shokran' => '3afwan! Let us know if you need anything else',
                'shukran' => '3afwan! Let us know if you need anything else',
                'mersi' => 'Mersi! Da wa2ebna. Need anything else?',
                'thnx' => 'Mersi! Da wa2ebna. Need anything else?',
                'tamam' => 'Tamam! Let us know if you need help',
            ],
            'apologies' => [
                // Arabic
                'أسف' => 'لا داعي للأسف! كيف يمكنني مساعدتك؟',
                'اسف' => 'لا داعي للأسف! كيف يمكنني مساعدتك؟',
                'عذراً' => 'عذراً! هل تحتاج أي مساعدة؟',
                'عذرا' => 'عذراً! هل تحتاج أي مساعدة؟',
                'معليش' => 'معليش! كيف أقدر أساعدك؟',
                'معلش' => 'معليش! كيف أقدر أساعدك؟',
                'آسف' => 'لا داعي للأسف! هل تحتاج أي شيء؟',
    
                // English
                'sorry' => 'No need to apologize! How can I assist you?',
                'soz' => 'No need to apologize! How can I assist you?',
                'my bad' => 'No worries! What can I do for you?',
                'apologies' => 'No problem! How can I help?',
    
                // Franco
                'asif' => 'Malesh! How can I help?',
                'asef' => 'Malesh! How can I help?',
                'ma3lesh' => 'Ma3lesh! What do you need?',
                'ma3lesh' => 'Ma3lesh! What do you need?',
            ],
            'compliments' => [
                // Arabic
                'جميل' => 'شكراً! كيف يمكنني مساعدتك؟',
                'جمايل' => 'شكراً! كيف يمكنني مساعدتك؟',
                'رائع' => 'شكراً! هل تحتاج أي مساعدة؟',
                'رايع' => 'شكراً! هل تحتاج أي مساعدة؟',
                'حلو' => 'شكراً! كيف أقدر أساعدك؟',
                'حلوو' => 'شكراً! كيف أقدر أساعدك؟',
                'ممتاز' => 'شكراً! هل تحتاج أي شيء؟',
                'ممتازة' => 'شكراً! هل تحتاج أي شيء؟',
    
                // English
                'great' => 'Thank you! How can I assist you?',
                'gr8' => 'Thank you! How can I assist you?',
                'awesome' => 'Thanks! What can I do for you?',
                'awsom' => 'Thanks! What can I do for you?',
                'nice' => 'Thank you! How can I help?',
                'noice' => 'Thank you! How can I help?',
                'cool' => 'Thanks! What do you need?',
                'coool' => 'Thanks! What do you need?',
    
                // Franco
                'gameel' => 'Shokran! How can I help?',
                'gamal' => 'Shokran! How can I help?',
                '7elw' => 'Shokran! What do you need?',
                'helw' => 'Shokran! What do you need?',
            ],
            'general' => [
                // Arabic
                'كيف حالك' => 'أنا بخير، شكراً! كيف يمكنني مساعدتك؟',
                'كيفك' => 'أنا بخير، شكراً! كيف يمكنني مساعدتك؟',
                'كيف الحال' => 'أنا بخير، شكراً! كيف يمكنني مساعدتك؟',
                'أخبارك ايه' => 'كل شيء تمام، شكراً! كيف أقدر أساعدك؟',
                'اخبارك ايه' => 'كل شيء تمام، شكراً! كيف أقدر أساعدك؟',
                'عامل ايه' => 'أنا تمام، شكراً! هل تحتاج أي مساعدة؟',
                'عاملاه' => 'أنا تمام، شكراً! هل تحتاج أي مساعدة؟',
    
                // English
                'how are you' => 'I\'m good, thank you! How can I assist you?',
                'hru' => 'I\'m good, thank you! How can I assist you?',
                'how\'s it going' => 'All good, thanks! What can I do for you?',
                'how goes' => 'All good, thanks! What can I do for you?',
                'what\'s up' => 'Not much, thanks! How can I help?',
                'sup' => 'Not much, thanks! How can I help?',
    
                // Franco
                'ezayak' => 'Ana tamam, shokran! How can I help?',
                'ezayek' => 'Ana tamam, shokran! How can I help?',
                '3amel eh' => 'Kolo tamam, shokran! What do you need?',
                '3amla eh' => 'Kolo tamam, shokran! What do you need?',
            ],
        ];
    
        foreach ($casualPhrases as $category => $phrases) {
            foreach ($phrases as $phrase => $response) {
                if (mb_strpos(mb_strtolower($message), mb_strtolower($phrase)) !== false) {
                    return $response;
                }
            }
        }
    
        return null;
    }
    private function normalizeText($text)
    {
        $replacements = [
            'ة' => 'ه',
            'إ' => 'ا',
            'أ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ئ' => 'ي',
            'ؤ' => 'و',
            'اّ' => 'ا',
            'ـ' => '',
            'َ' => '',
            'ُ' => '',
            'ِ' => '',
            'ّ' => '',
            'ْ' => '',
            'ٌ' => '',
            'ً' => '',
            'ٍ' => '',
            'ٰ' => '',
            'ٔ' => '',
            '�' => ''
        ];
        return strtr($text, $replacements);
    }
    private function getFallbackResponse()
    {
        $responses = [
            'عذرًا، لم أفهم السؤال. سيتم تحويلك لخدمة العملاء خلال 12 ساعة.',
            'أسف، لم أستطع فهم الطلب. فريق الدعم سيتواصل معك قريبًا.',
            'عذرًا، لم أتمكن من فهم رسالتك. سيتم تحويلك لفريق الدعم.',
            'لم أتمكن من فهم سؤالك. سيتم تحويلك لخدمة العملاء.',
            'عذرًا، لم أستطع معالجة طلبك. فريق الدعم سيتواصل معك.',
            'لم أفهم ما تقصده. سيتم تحويلك لفريق الدعم.',
            'عذرًا، لم أتمكن من مساعدتك الآن. سيتم تحويلك لخدمة العملاء.',
            'أسف، لم أستطع فهم رسالتك. فريق الدعم سيتواصل معك.',
            'عذرًا، لم أتمكن من معالجة طلبك. سيتم تحويلك لفريق الدعم.',
            'لم أفهم سؤالك. سيتم تحويلك لخدمة العملاء.'
        ];

        $fallbackResponse = $responses[array_rand($responses)];

        return $fallbackResponse;
    }
}
