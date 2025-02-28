<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private $apiKey;
    private $httpClient;
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    // Common raffle questions and their predefined answers
    private const COMMON_QUESTIONS = [
        'process' => "Here's the participation process:\n1. Find an active raffle (green badge)\n2. Click 'View' to open it\n3. Click 'Join Raffle'\n4. Confirm participation\n\nRemember: You must be logged in! ✨",
        'participate' => "Want to join a raffle? Here's how:\n1. Look for raffles with green status\n2. Click 'View' button\n3. Hit 'Join Raffle'\n4. Done! Wait for results\n\nMake sure you're logged in first! 🎯",
        'how join' => "Here's how to join:\n1. Click 'View' on any raffle\n2. Click 'Join Raffle'\n3. Confirm and wait\n\nTip: Look for green status badges! 🎉",
        'rules' => "📜 Raffle Rules:\n- Must be logged in\n- One entry per raffle\n- Green = Active, can join\n- Gray = Ended\n- Winners picked randomly",
        'status' => "🎯 Raffle Status:\n- Green badge = Active, join now!\n- Gray badge = Ended\n\nOnly active raffles accept entries!",
        'winner' => "🏆 Winner Selection:\n- Random selection at raffle end\n- Check status to see if ended\n- Winners shown on raffle page",
        'start' => "Starting times:\n- Each raffle shows start/end times\n- Join any time before end\n- Check the green badge! ⏰",
        'active' => "Active raffles:\n- Look for green status badges\n- Join while they're active\n- One entry per person 🎯",
        'ended' => "Ended raffles:\n- Show gray status badge\n- Winner already picked\n- Can't join anymore ⌛",
    ];

    private const GREETINGS = [
        'hi' => "👋 Hello! I'm your raffle assistant. How can I help you with raffles today?",
        'hello' => "Hi there! 👋 I'm here to help you with raffles. What would you like to know?",
        'hey' => "Hey! 😊 Ready to help you with any raffle questions!",
        'good morning' => "Good morning! 🌅 Ready to help you explore our raffles!",
        'good afternoon' => "Good afternoon! ☀️ Looking to join a raffle today?",
        'good evening' => "Good evening! 🌙 Need help with our raffles?",
        'hola' => "Hola! 👋 I'm your raffle guide - what would you like to know?",
        'bonjour' => "Bonjour! 👋 Ready to help you with all things raffles!",
        'sup' => "Hey there! 👋 Ready to talk about raffles!",
        'yo' => "Hey! Ready to help you win some raffles! What's on your mind? 🎯",
    ];

    private const RAFFLE_KEYWORDS = [
        'raffle', 'join', 'participate', 'win', 'ticket', 'prize', 'winner', 'draw', 'enter',
        'how', 'active', 'ended', 'status', 'rules', 'start', 'end', 'time', 'process',
        'step', 'help', 'guide', 'when', 'where', 'what', 'need', 'requirement', 'cost',
        'free', 'paid', 'enter', 'participation', 'cancel', 'confirm', 'submit', 'button',
        'click', 'view', 'badge', 'green', 'gray', 'grey', 'done', 'finish', 'complete',
        'success', 'random', 'chance', 'luck', 'participate', 'get in', 'sign up', 'register',
        'help', 'assist', 'support', 'guide', 'info', 'information',
        'hi', 'hello', 'hey', 'morning', 'afternoon', 'evening', 'hola', 'bonjour',
        'good', 'greetings', 'sup', 'yo', 'thanks', 'thank', 'bye', 'goodbye',
        'explain', 'tell', 'show', 'work', 'mean', 'detail', 'more', 'about'
    ];

    private const THANK_YOU_RESPONSES = [
        'thank' => "You're welcome! 😊 Happy to help with your raffle questions!",
        'thanks' => "You're welcome! 🌟 Let me know if you need anything else about the raffles!",
        'thx' => "No problem at all! 👍 Feel free to ask more about raffles!",
        'ty' => "You're welcome! 🎉 Good luck with the raffles!",
        'merci' => "De rien! 🌟 Always here to help with raffles!",
        'gracias' => "¡De nada! 🎯 Happy to help with the raffles!",
        'appreciate' => "Happy to help! 😊 Good luck with the raffles!"
    ];

    public function __construct(
        HttpClientInterface $httpClient,
        string $geminiApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
    }

    private function isRaffleRelated(string $message): bool
    {
        $message = strtolower($message);
        foreach (self::RAFFLE_KEYWORDS as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function findMatchingPredefinedAnswer(string $message): ?string
    {
        $message = strtolower($message);
        $words = explode(' ', $message);
        
        foreach (self::COMMON_QUESTIONS as $keyword => $answer) {
            $keywordParts = explode(' ', $keyword);
            $matches = 0;
            
            // Check each part of the keyword against each word in the message
            foreach ($keywordParts as $part) {
                foreach ($words as $word) {
                    if (strpos($word, $part) !== false || strpos($part, $word) !== false) {
                        $matches++;
                        break;
                    }
                }
            }
            
            // If all parts of the keyword are found, return the answer
            if ($matches === count($keywordParts)) {
                return $answer;
            }
        }

        // Check for single keyword matches if no full phrase match
        foreach ($words as $word) {
            foreach (self::COMMON_QUESTIONS as $keyword => $answer) {
                if (strpos($word, $keyword) !== false || strpos($keyword, $word) !== false) {
                    return $answer;
                }
            }
        }

        return null;
    }

    private function isGreeting(string $message): bool
    {
        $message = strtolower($message);
        foreach (self::GREETINGS as $greeting => $_) {
            if (strpos($message, $greeting) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getGreetingResponse(string $message): string
    {
        $message = strtolower($message);
        foreach (self::GREETINGS as $greeting => $response) {
            if (strpos($message, $greeting) !== false) {
                return $response;
            }
        }
        return self::GREETINGS['hello']; // Default greeting
    }

    private function isThankYou(string $message): bool
    {
        $message = strtolower($message);
        foreach (array_keys(self::THANK_YOU_RESPONSES) as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getThankYouResponse(string $message): string
    {
        $message = strtolower($message);
        foreach (self::THANK_YOU_RESPONSES as $keyword => $response) {
            if (strpos($message, $keyword) !== false) {
                return $response;
            }
        }
        return self::THANK_YOU_RESPONSES['thank']; // Default thank you response
    }

    public function generateResponse(string $userMessage): string
    {
        // Check for thank you messages first
        if ($this->isThankYou($userMessage)) {
            return $this->getThankYouResponse($userMessage);
        }

        // Check for greetings next
        if ($this->isGreeting($userMessage)) {
            return $this->getGreetingResponse($userMessage);
        }

        if (!$this->isRaffleRelated($userMessage)) {
            return "👋 Hi! While I'd love to chat, I'm specifically here to help with raffles. Feel free to ask me about joining raffles, rules, or anything raffle-related!";
        }

        // Check for predefined answers first
        $predefinedAnswer = $this->findMatchingPredefinedAnswer($userMessage);
        if ($predefinedAnswer !== null) {
            return $predefinedAnswer;
        }

        try {
            $context = "You are a helpful NFT raffle assistant. Keep responses friendly and focused on raffles. Here's what you know:

            JOINING PROCESS:
            1. Browse available raffles
            2. Click 'View' button
            3. Click 'Join Raffle'
            4. Must be logged in
            5. One entry per raffle

            RULES:
            - Login required
            - One entry per raffle
            - Green status = active
            - Gray status = ended
            - Random winner selection

            STATUS INFO:
            - Active: Can join
            - Ended: Winner selected
            - Must join before end time
            - Equal winning chance
            
            Be concise and clear in your responses. If unsure, explain how to join a raffle.";

            $response = $this->httpClient->request('POST', self::API_URL . '?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $context . "\n\nQuestion: " . $userMessage . "\n\nGive a helpful, concise response:"]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 200,
                    ]
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $answer = $data['candidates'][0]['content']['parts'][0]['text'];
                // Clean up the response
                $answer = str_replace(['Question:', 'Response:', 'Answer:'], '', $answer);
                $answer = trim($answer);
                return $answer;
            }
            
            // If API response is invalid, return a default helpful message
            return "To join a raffle:\n1. Click 'View' on the raffle\n2. Click 'Join Raffle'\n3. Confirm participation\n\nMake sure you're logged in first!";
            
        } catch (\Exception $e) {
            error_log('Gemini API Error: ' . $e->getMessage());
            // Return the most relevant predefined answer as fallback
            return self::COMMON_QUESTIONS['how to join'];
        }
    }
}