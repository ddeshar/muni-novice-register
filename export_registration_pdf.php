<?php
require 'db.php';

function is_valid_export_token($id, $token)
{
    if ($id <= 0 || $token === '') {
        return false;
    }

    $expected = hash_hmac('sha256', (string)$id, APP_SECRET);
    return hash_equals($expected, (string)$token);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = $_GET['token'] ?? '';

if (!isset($_SESSION['admin']) && !is_valid_export_token($id, $token)) {
    header('Location: ' . ADMIN_LOGIN_PATH);
    exit;
}

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid registration id');
}

$stmt = $conn->prepare('SELECT * FROM registrations WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    exit('Registration not found');
}
?>
<!DOCTYPE html>
<html lang="ne">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration PDF - #<?php echo (int)$user['id']; ?></title>
    <style>
        @page {
            size: A4;
            margin: 14mm;
        }

        body {
            font-family: "Noto Sans Devanagari", "Nirmala UI", sans-serif;
            margin: 0;
            color: #111;
            background: #fff;
            line-height: 1.45;
        }

        .sheet {
            border: 1px solid #bbb;
            padding: 14px;
        }

        .head {
            text-align: center;
            margin-bottom: 12px;
        }

        .head h2 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .head p {
            margin: 2px 0;
            font-size: 12px;
        }

        .row {
            display: flex;
            gap: 14px;
        }

        .photo {
            width: 130px;
            min-width: 130px;
            height: 160px;
            border: 1px solid #888;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 11px;
            color: #666;
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .fields {
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        td {
            padding: 6px 4px;
            border-bottom: 1px dotted #777;
            vertical-align: top;
        }

        td:first-child {
            width: 40%;
            color: #333;
        }

        .consent {
            margin-top: 14px;
            padding: 10px;
            border: 1px solid #bbb;
            font-size: 12px;
        }

        .doc-note {
            margin-top: 10px;
            padding: 10px;
            border: 1px dashed #666;
            font-size: 12px;
            background: #fafafa;
        }

        .footer {
            margin-top: 22px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .print-only {
            display: none;
        }

        .toolbar {
            margin: 12px auto;
            max-width: 820px;
            display: flex;
            gap: 8px;
        }

        .btn {
            border: 1px solid #222;
            background: #fff;
            color: #222;
            padding: 8px 12px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        @media print {
            .toolbar {
                display: none;
            }

            .sheet {
                border: 0;
                padding: 0;
            }

            .print-only {
                display: inline;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button class="btn" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn" href="user_details.php?id=<?php echo (int)$user['id']; ?>">Back</a>
    </div>

    <div class="sheet">
        <div class="head">
            <h2>मुनि विहार (श्री धम्मोत्तम महाविहार)</h2>
            <p>इनाचो टोल, वडा नं. ७, भक्तपुर नगरपालिका, भक्तपुर जिल्ला, बागमती प्रदेश, नेपाल।</p>
            <p>सामूहिक प्रब्रज्या तथा उपसम्पदा योजनामा सहभागिताको लागि आवेदन-पत्र</p>
        </div>

        <div class="row">
            <div class="fields">
                <table>
                    <tr>
                        <td>दर्ता नम्बर</td>
                        <td>#<?php echo (int)$user['id']; ?></td>
                    </tr>
                    <tr>
                        <td>नाम</td>
                        <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>जन्म मिति</td>
                        <td><?php echo htmlspecialchars($user['dob'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>उत्तीर्ण कक्षा</td>
                        <td><?php echo htmlspecialchars($user['passed_class'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>विद्यालयको नाम</td>
                        <td><?php echo htmlspecialchars($user['school_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>आमाको नाम</td>
                        <td><?php echo htmlspecialchars($user['mother_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>बाबुको नाम</td>
                        <td><?php echo htmlspecialchars($user['father_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>स्थायी ठेगाना</td>
                        <td><?php echo htmlspecialchars($user['permanent_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>अस्थायी ठेगाना</td>
                        <td><?php echo htmlspecialchars($user['temporary_address'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>फोन नम्बर</td>
                        <td><?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>स्थिति</td>
                        <td><?php echo htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>दर्ता मिति</td>
                        <td><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="photo">
                <?php if (!empty($user['photo'])): ?>
                    <img src="uploads/<?php echo rawurlencode($user['photo']); ?>" alt="Photo">
                <?php else: ?>
                    फोटो उपलब्ध छैन
                <?php endif; ?>
            </div>
        </div>

        <div class="consent">
            उपरोक्त विवरणहरू सही छन् भनी स्वीकार गरिन्छ। आवेदन प्रक्रियासम्बन्धी आवश्यक नियमहरू पालना गरिनेछ।
        </div>

        <div class="doc-note">
            जन्मदर्ताको प्रमाणपत्र, विद्यालयको प्रमाणपत्र, मार्कशीट, स्थनान्तरणपत्र,
            माता पितासँग नाता प्रमाणपत्र जस्ता महत्त्वपूर्ण कागजातहरू पनि साथमा ल्याउनु होला ।
        </div>

        <div class="footer">
            <div>आवेदकको हस्ताक्षर: ____________________</div>
            <div>प्रमाणित गर्ने अधिकारी: ____________________</div>
        </div>
    </div>
</body>

</html>
