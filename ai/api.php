<?php
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$domain = explode(':', $http_host)[0];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (!empty($origin)) {
    $parsed_url = parse_url($origin);
    $origin_host = $parsed_url['host'] ?? '';
    if ($origin_host === 'localhost' || $origin_host === '127.0.0.1' || $origin_host === $domain || str_ends_with($origin_host, '.' . $domain)) {
        header("Access-Control-Allow-Origin: " . $origin);
    } else {
        header("Access-Control-Allow-Origin: http://" . $domain);
    }
} else {
    header("Access-Control-Allow-Origin: http://" . $domain);
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
require_once('../config/database.php');

$data = json_decode(file_get_contents("php://input"));
$query = isset($data->query) ? trim($data->query) : '';
$session_id = session_id();

if (empty($query)) {
    echo json_encode(["status" => false, "message" => "Empty query."]);
    exit;
}

// ----------------------------------------------------
// 1. LEAD CAPTURE SYSTEM DETECTOR
// ----------------------------------------------------
// Check if the user query represents an admission callback lead request
// Format expected: "lead: Name, Phone, Email, Class query"
if (stripos($query, 'lead:') === 0) {
    $parts = explode(',', substr($query, 5));
    $name = isset($parts[0]) ? sanitize($parts[0]) : 'Anonymous Lead';
    $phone = isset($parts[1]) ? sanitize($parts[1]) : '';
    $email = isset($parts[2]) ? sanitize($parts[2]) : '';
    $class_query = isset($parts[3]) ? sanitize($parts[3]) : 'Admission Enquiry';

    try {
        $stmt_lead = $pdo->prepare("INSERT INTO admission_leads (name, phone, email, query) VALUES (?, ?, ?, ?)");
        $stmt_lead->execute([$name, $phone, $email, $class_query]);
        
        // Also save to CRM Leads
        $stmt_crm = $pdo->prepare("INSERT INTO crm_leads (school_id, name, email, phone, class_applied, message, source, status) VALUES (?, ?, ?, ?, ?, ?, 'Chatbot', 'New Lead')");
        $stmt_crm->execute([CURRENT_SCHOOL_ID, $name, $email, $phone, null, $class_query]);
        
        // Log chat history
        $stmt_log = $pdo->prepare("INSERT INTO ai_chat_history (session_id, role, content) VALUES (?, 'user', ?)");
        $stmt_log->execute([$session_id, $query]);
        
        $reply = "Dhanyawad! Aapki callback request humne register kar li hai. Humare administrative officer jald hi aapko call karenge.";
        
        $stmt_reply = $pdo->prepare("INSERT INTO ai_chat_history (session_id, role, content) VALUES (?, 'assistant', ?)");
        $stmt_reply->execute([$session_id, $reply]);

        echo json_encode(["status" => true, "reply" => $reply]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(["status" => false, "message" => "Lead capture failed: " . $e->getMessage()]);
        exit;
    }
}

// ----------------------------------------------------
// 2. FETCH AI SETTINGS
// ----------------------------------------------------
$api_key = '';
$model = 'gpt-5.5-mini';
$temperature = 0.7;
$token_limit = 2000;
$system_prompt = '';

try {
    $stmt_set = $pdo->query("SELECT * FROM ai_settings LIMIT 1");
    $settings = $stmt_set->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $api_key = $settings['api_key'] ?? '';
        $model = $settings['model'] ?? 'gpt-5.5-mini';
        $temperature = (float)($settings['temperature'] ?? 0.7);
        $token_limit = (int)($settings['token_limit'] ?? 2000);
        $system_prompt = $settings['system_prompt'] ?? '';
    }
} catch (PDOException $e) {
    // Ignore and fallback
}

if (empty($system_prompt)) {
    // Load default prompt from text file
    $prompt_file = __DIR__ . '/prompts/school-assistant.txt';
    if (file_exists($prompt_file)) {
        $system_prompt = file_get_contents($prompt_file);
    } else {
        $system_prompt = "You are a School ERP Assistant.";
    }
}

// Log user query in chat history
try {
    $stmt_log = $pdo->prepare("INSERT INTO ai_chat_history (session_id, role, content) VALUES (?, 'user', ?)");
    $stmt_log->execute([$session_id, $query]);
} catch (PDOException $e) {
    // Ignore log failure
}

// ----------------------------------------------------
// 3. ATTEMPT OPENAI COMPLETIONS CALL (IF API KEY PRESENT)
// ----------------------------------------------------
$ai_reply = '';
if (!empty($api_key)) {
    try {
        $messages = [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => $query]
        ];

        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => $model,
            "messages" => $messages,
            "temperature" => $temperature,
            "max_tokens" => $token_limit
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        curl_close($ch);

        if ($res) {
            $res_data = json_decode($res, true);
            if (isset($res_data['choices'][0]['message']['content'])) {
                $ai_reply = $res_data['choices'][0]['message']['content'];
            }
        }
    } catch (Exception $e) {
        // Fallback to local Smart FAQ search if curl fails
    }
}

// ----------------------------------------------------
// 4. SMART FAQ SEARCH FALLBACK (Levenshtein / similar_text)
// ----------------------------------------------------
if (empty($ai_reply)) {
    $best_match = null;
    $highest_similarity = 0;

    try {
        $faqs = $pdo->query("SELECT * FROM faq_questions")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($faqs as $faq) {
            // Check similarity using similar_text
            similar_text(strtolower($query), strtolower($faq['question']), $percent);
            
            // Check similarity using levenshtein distance
            $lev = levenshtein(strtolower($query), strtolower($faq['question']));
            $max_len = max(strlen($query), strlen($faq['question']));
            $lev_percent = $max_len > 0 ? (1 - ($lev / $max_len)) * 100 : 0;
            
            $sim = max($percent, $lev_percent);
            
            if ($sim > $highest_similarity) {
                $highest_similarity = $sim;
                $best_match = $faq['answer'];
            }
        }
    } catch (PDOException $e) {
        // Ignore
    }

    if ($highest_similarity >= 40 && !empty($best_match)) {
        $ai_reply = $best_match;
    } else {
        // Fallback hinge responses
        if (preg_match('/(hi|hello|hey|namaste)/i', $query)) {
            $ai_reply = "Namaste! Main VIC School AI Assistant hoon. Main aapki kya sahayata kar sakta hoon? Aap timing, transport, fees, ya admission ke baare me pooch sakte hain.";
        } else if (preg_match('/(admission|class|9|10|11|12|admission open)/i', $query)) {
            $ai_reply = "Ji haan! Admissions open hain. Class 9 aur baaki classes ke liye zaroori documents hain:\n• Aadhaar Card\n• Previous Marksheet\n• Transfer Certificate\n\nKya aap callback request karna chahenge? Apna callback request register karne ke liye type karein: `lead: Name, Phone, Email, Query` (jaise: `lead: Rahul Sharma, 9876543210, rahul@mail.com, Class 9 admission`)";
        } else {
            $ai_reply = "Main aapki query poori tarah samajh nahi paya. Timing, contact details, ya admission documents ke baare me poochein. Ya aap callback request ke liye register kar sakte hain: type `lead: Name, Phone, Email, Class`";
        }
    }
}

// Log AI response in chat history
try {
    $stmt_rep = $pdo->prepare("INSERT INTO ai_chat_history (session_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt_rep->execute([$session_id, $ai_reply]);
} catch (PDOException $e) {
    // Ignore
}

echo json_encode(["status" => true, "reply" => $ai_reply]);
exit;
?>
