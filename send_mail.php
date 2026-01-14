<?php
// エラー表示設定（本番環境では off にすることを推奨）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// セキュリティヘッダー
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// POSTリクエストのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

// 送信先メールアドレス（必ず変更してください）
$to = 'takayuki.f@itnav.co.jp';

// 入力値の取得とサニタイズ
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// 改行コードの除去（ヘッダーインジェクション対策）
function remove_newlines($string) {
    return str_replace(array("\r", "\n", "%0a", "%0d"), '', $string);
}

// フォームデータの取得
$company = isset($_POST['company']) ? sanitize_input($_POST['company']) : '';
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$tel = isset($_POST['tel']) ? sanitize_input($_POST['tel']) : '';
$plan = isset($_POST['plan']) ? sanitize_input($_POST['plan']) : '';
$purpose = isset($_POST['purpose']) ? sanitize_input($_POST['purpose']) : '';
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

// 必須項目のチェック
$errors = array();

if (empty($company)) {
    $errors[] = '会社名を入力してください。';
}

if (empty($name)) {
    $errors[] = 'お名前を入力してください。';
}

if (empty($email)) {
    $errors[] = 'メールアドレスを入力してください。';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '有効なメールアドレスを入力してください。';
}

if (empty($plan)) {
    $errors[] = 'ご希望のプランを選択してください。';
}

if (empty($purpose)) {
    $errors[] = 'お問い合わせ内容を選択してください。';
}

if (empty($message)) {
    $errors[] = '具体的なご相談内容を入力してください。';
}

// エラーがある場合はエラーページを表示
if (!empty($errors)) {
    echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>入力エラー | PROTOTYPE LAB</title>
    <style>
        body {
            font-family: "Noto Sans JP", sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        .error-box {
            background: #fee;
            border: 2px solid #c33;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        .error-box h1 {
            color: #c33;
            margin-bottom: 20px;
        }
        .error-list {
            text-align: left;
            margin: 20px 0;
            color: #333;
        }
        .back-button {
            display: inline-block;
            background: #4a90e2;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-button:hover {
            background: #357abd;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>入力エラー</h1>
        <div class="error-list">
            <ul>';
    foreach ($errors as $error) {
        echo '<li>' . $error . '</li>';
    }
    echo '</ul>
        </div>
        <a href="javascript:history.back();" class="back-button">戻る</a>
    </div>
</body>
</html>';
    exit;
}

// プラン名の変換
$plan_names = array(
    'light' => 'ライト（¥100,000/月）',
    'standard' => 'スタンダード（¥300,000/月）',
    'pro' => 'プロ（¥500,000~/月）',
    'undecided' => '未定・相談したい'
);
$plan_text = isset($plan_names[$plan]) ? $plan_names[$plan] : $plan;

// お問い合わせ内容の変換
$purpose_names = array(
    'sales' => '営業プレゼン支援',
    'newbusiness' => '新規事業検証',
    'lp' => '緊急LP・フォーム制作',
    'demo' => '開発営業の武器化',
    'other' => 'その他'
);
$purpose_text = isset($purpose_names[$purpose]) ? $purpose_names[$purpose] : $purpose;

// メール件名
$subject = '【PROTOTYPE LAB】お問い合わせを受け付けました';

// メール本文の作成
$body = "PROTOTYPE LAB お問い合わせフォームから送信されました。\n\n";
$body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$body .= "■ 会社名\n";
$body .= $company . "\n\n";
$body .= "■ お名前\n";
$body .= $name . "\n\n";
$body .= "■ メールアドレス\n";
$body .= $email . "\n\n";
$body .= "■ 電話番号\n";
$body .= !empty($tel) ? $tel : '未記入' . "\n\n";
$body .= "■ ご希望のプラン\n";
$body .= $plan_text . "\n\n";
$body .= "■ お問い合わせ内容\n";
$body .= $purpose_text . "\n\n";
$body .= "■ 具体的なご相談内容\n";
$body .= $message . "\n\n";
$body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$body .= "送信日時: " . date('Y年m月d日 H:i:s') . "\n";
$body .= "送信元IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

// 改行コードの統一
$body = str_replace("\r\n", "\n", $body);
$body = str_replace("\r", "\n", $body);

// メールヘッダーの作成（ヘッダーインジェクション対策）
$from = remove_newlines($email);
$from_name = remove_newlines($name);

// mb_send_mail用のヘッダー設定
mb_language('Japanese');
mb_internal_encoding('UTF-8');

$headers = "From: " . mb_encode_mimeheader($from_name) . " <" . $from . ">\n";
$headers .= "Reply-To: " . $from . "\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// メール送信
$mail_sent = mb_send_mail($to, $subject, $body, $headers);

// 自動返信メール（お客様へ）
$auto_reply_subject = '【PROTOTYPE LAB】お問い合わせありがとうございます';
$auto_reply_body = $name . " 様\n\n";
$auto_reply_body .= "この度は、PROTOTYPE LAB にお問い合わせいただき、誠にありがとうございます。\n\n";
$auto_reply_body .= "以下の内容でお問い合わせを受け付けいたしました。\n";
$auto_reply_body .= "担当者より、2営業日以内にご連絡させていただきます。\n\n";
$auto_reply_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$auto_reply_body .= "■ 会社名\n";
$auto_reply_body .= $company . "\n\n";
$auto_reply_body .= "■ お名前\n";
$auto_reply_body .= $name . "\n\n";
$auto_reply_body .= "■ メールアドレス\n";
$auto_reply_body .= $email . "\n\n";
$auto_reply_body .= "■ 電話番号\n";
$auto_reply_body .= !empty($tel) ? $tel : '未記入' . "\n\n";
$auto_reply_body .= "■ ご希望のプラン\n";
$auto_reply_body .= $plan_text . "\n\n";
$auto_reply_body .= "■ お問い合わせ内容\n";
$auto_reply_body .= $purpose_text . "\n\n";
$auto_reply_body .= "■ 具体的なご相談内容\n";
$auto_reply_body .= $message . "\n\n";
$auto_reply_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$auto_reply_body .= "※このメールは自動送信されています。\n";
$auto_reply_body .= "※お心当たりのない場合は、お手数ですが削除をお願いいたします。\n\n";
$auto_reply_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$auto_reply_body .= "株式会社イトナブ | PROTOTYPE LAB.\n";
$auto_reply_body .= "Email: contact@itnav.co.jp\n";
$auto_reply_body .= "Web: https://itnav.co.jp/\n";
$auto_reply_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$auto_reply_headers = "From: PROTOTYPE LAB <contact@itnav.co.jp>\n";
$auto_reply_headers .= "Reply-To: contact@itnav.co.jp\n";
$auto_reply_headers .= "Content-Type: text/plain; charset=UTF-8\n";
$auto_reply_headers .= "X-Mailer: PHP/" . phpversion();

// 自動返信メール送信
mb_send_mail($from, $auto_reply_subject, $auto_reply_body, $auto_reply_headers);

// 送信結果によってリダイレクト
if ($mail_sent) {
    // 成功時
    header('Location: thanks.html');
    exit;
} else {
    // 失敗時
    echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信エラー | PROTOTYPE LAB</title>
    <style>
        body {
            font-family: "Noto Sans JP", sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        .error-box {
            background: #fee;
            border: 2px solid #c33;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        .error-box h1 {
            color: #c33;
            margin-bottom: 20px;
        }
        .back-button {
            display: inline-block;
            background: #4a90e2;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-button:hover {
            background: #357abd;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>送信エラー</h1>
        <p>申し訳ございません。メールの送信に失敗しました。</p>
        <p>お手数ですが、しばらく時間をおいて再度お試しいただくか、<br>
        直接メールにてお問い合わせください。</p>
        <p><strong>Email:</strong> contact@itnav.co.jp</p>
        <a href="contact.html" class="back-button">お問い合わせフォームに戻る</a>
    </div>
</body>
</html>';
    exit;
}
?>
