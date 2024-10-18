<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>飛鵝數位電訊FT UTM Link Generator</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link href="./img/favicon.ico" rel="shortcut icon"/>
</head>
<body>
    <div class="container">
        <h2>UTM Link Generator</h2>
        <form id="utmForm" method="post">
            <p style="font-size:12px;">因API限制，每小時僅能縮100則網址，請謹慎輸入；所有欄位皆為必填項目。</p>
            <div class="form-group">
                <label for="url">URL:</label>
                <input type="text" class="form-control" id="url" name="url" placeholder="請輸入目標網址" required>
            </div>
            <div class="form-group">
                <label for="utm_id">UTM ID:（識別用，可以用日期或時間。 ex：20241017）</label>
                <input type="text" class="form-control" id="utm_id" name="utm_id" pattern="[a-zA-Z0-9_]+" title="只能包含英文字母、數字和底線" placeholder="只能包含英文字母、數字和底線" required>
            </div>
            <div class="form-group">
                <label for="utm_term">UTM Term:（關鍵字廣告所用的關鍵字，可以包含字母、數字和底線）</label>
                <input type="text" class="form-control" id="utm_term" name="utm_term" pattern="[a-zA-Z0-9_]+" title="只能包含英文字母、數字和底線" placeholder="只能包含英文字母、數字和底線" required>
            </div>
            <div class="form-group">
                <label for="utm_content">UTM Content:<br>（例如在FB的ADS同時投兩個廣告，設定不同參數用以區分。 ex：ads1 or ads2）</label>
                <textarea class="form-control" id="utm_content" name="utm_content" pattern="[a-zA-Z0-9_]+" title="只能包含英文字母、數字和底線" placeholder="只能包含英文字母、數字和底線"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">產生連結</button>
        </form>
        <div id="result"></div>
    </div>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $url = $_POST['url'];
        $utm_id = $_POST['utm_id'];
        $utm_term = $_POST['utm_term'];
        $utm_content = $_POST['utm_content'];
        
        // 自動補全 https://
        if (!preg_match("/^https?:\/\//", $url)) {
            $url = 'https://' . $url;
        }

        // 檢查是否含有中文或特殊符號
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $utm_id) || !preg_match("/^[a-zA-Z0-9_]+$/", $utm_term) || !preg_match("/^[a-zA-Z0-9_]+$/", $utm_content)) {
            echo '<script>alert("UTM ID、UTM Term 和 UTM Content 只能包含英文字母、數字和底線！");</script>';
            exit;
        }

        $channels = [
            ['source' => 'fb', 'medium' => 'post', 'campaign' => 'page'],
            ['source' => 'fb', 'medium' => 'cpc', 'campaign' => 'ADS'],
            ['source' => 'ig', 'medium' => 'post', 'campaign' => 'page'],
            ['source' => 'ig', 'medium' => 'cpc', 'campaign' => 'ADS'],
            ['source' => 'line', 'medium' => 'linebiz', 'campaign' => 'OAM'],
            ['source' => 'line', 'medium' => 'LAP', 'campaign' => 'OAM'],
            ['source' => 'line', 'medium' => 'post', 'campaign' => 'openchat'],
            ['source' => 'line', 'medium' => 'post', 'campaign' => 'linegroup']
        ];

        $results = '';
        foreach ($channels as $channel) {
            $link = $url . '?utm_source=' . $channel['source'] .
                           '&utm_medium=' . $channel['medium'] .
                           '&utm_campaign=' . $channel['campaign'] .
                           '&utm_id=' . $utm_id .
                           '&utm_term=' . $utm_term .
                           '&utm_content=' . $utm_content;
            try {
                $shortUrl = shortenUrl($link, $channel['source']);
                $results .= '<div class="result-container"><p><strong>' . strtoupper($channel['source'] . ' ' . $channel['medium']) . '</strong>: <a href="' . $shortUrl . '" target="_blank">' . $shortUrl . '</a><button class="btn btn-success copy-btn" onclick="copyToClipboard(this, \'' . $shortUrl . '\')">複製</button></p><p><button class="btn btn-link collapseLinkcolor" type="button" data-toggle="collapse" data-target="#collapseLink' . $channel['source'] . $channel['medium'] . '">顯示原始網址</button></p><div class="collapse" id="collapseLink' . $channel['source'] . $channel['medium'] . '"><p>原始網址: ' . $link . '<button class="btn btn-info copy-btn-link" onclick="copyToClipboard(this, \'' . $link . '\')">複製</button></p></div></div>';
            } catch (Exception $e) {
                $results .= '<p>Error generating short URL for ' . strtoupper($channel['source'] . ' ' . $channel['medium']) . ': ' . $e->getMessage() . '</p>';
            }
        }

        echo '<div id="result">' . $results . '</div>';
    }

    function shortenUrl($longUrl, $source) {
        $apiKey = '4070ff49d794e73519523b663c974755ecd6b330919b04df8a38b58d65165567c4f5d6';
        $endpoint = 'https://api.reurl.cc/shorten';
        $postData = json_encode(['url' => $longUrl, 'utm_source' => $source]);

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n" .
                             "reurl-api-key: " . $apiKey . "\r\n",
                'method'  => 'POST',
                'content' => $postData
            ]
        ];

        $context  = stream_context_create($options);
        $response = file_get_contents($endpoint, false, $context);

        if ($response === FALSE) {
            throw new Exception('無法縮短 URL');
        }

        $responseData = json_decode($response, true);
        if ($responseData['res'] === 'success') {
            return $responseData['short_url'];
        } else {
            throw new Exception('縮短 URL 失敗: ' . $responseData['msg']);
        }
    }
    ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    function copyToClipboard(btn, text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                btn.textContent = '已複製'; // 修改按鈕文字為已複製
                setTimeout(function() {
                    btn.textContent = '複製'; // 2秒後恢復為複製
                }, 2000); // 顯示2秒後恢復原來的文字
            }).catch(function(err) {
                console.error('複製失敗: ', err);
                fallbackCopyTextToClipboard(text, btn);
            });
        } else {
            fallbackCopyTextToClipboard(text, btn);
        }
    }

    function fallbackCopyTextToClipboard(text, btn) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand('copy');
            var msg = successful ? '已複製' : '複製失敗';
            btn.textContent = msg;
            setTimeout(function() {
                btn.textContent = '複製'; // 2秒後恢復為複製
            }, 2000); // 顯示2秒後恢復原來的文字
        } catch (err) {
            console.error('複製失敗: ', err);
        }
        
        document.body.removeChild(textArea);
    }
</script>
<script src="./cursor-trail.min.js"></script>
		<script>
      cursorTrail({
        pattern: 'blueSparkles',
        animationType: 'down',
		duration: 3000,
		shapesCount: 50,
		theme: 'light'
      });
    </script>

</body>
</html>
