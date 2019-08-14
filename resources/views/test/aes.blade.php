<!DOCTYPE html>
<html>
<head>
    <title>Wallet - 测试页面</title>
    <meta charset="utf-8">
    <script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
    <!-- 最新版本的 Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- 可选的 Bootstrap 主题文件（一般不用引入） -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- 最新的 Bootstrap 核心 JavaScript 文件 -->
    <script src="https://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://cdn.bootcss.com/blueimp-md5/2.10.0/js/md5.js"></script>
    <style>
        .m-b-40{margin-bottom: 40px;}
        .m-t-20{margin-top: 20px;}
    </style>
</head>
<body>
<h3 class="text-center">欢迎进入 Wallet  测试页面</h3>
<div class="col-md-12">
    <div class="col-md-2"></div>
    <div class="col-md-8">
        <div class="input-box clearfix m-b-40">
            <h5>content</h5>
            <hr>
            <div class="col-md-3"><span>Content: </span><input type="text" id="content" value="" class="form-control"></div>
        </div>
        <div class="input-box clearfix m-b-40">
            <h5>结果</h5>
            <hr>
            <div class="col-md-3"><span>result: </span><textarea name="result" id="result" cols="400" rows="15" class="form-control"></textarea></div>
        </div>
        <div class="input-box clearfix m-b-40">
            <h5>操作</h5>
            <hr>
            <div class="col-md-12 m-t-20">
                <button class="btn btn-default" onclick="encodeContent();">加密</button>
                <button class="btn btn-default" onclick="decodeContent();">解密</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<script type="text/javascript">

    

    function encodeContent() {
        var params = {
            'key' : $('#content').val(),
            'aes_type' : 'encode'
        };

        var url = '/api/wallet/aes';

        console.log(params);
       ajax(url, params);
    }

    function decodeContent() {
        var params = {
            'key' : $('#content').val(),
            'aes_type' : 'decode'
        };

        var url = '/api/wallet/aes';

        console.log(params);
        ajax(url,params);
    }



    function ajax(url, params) {
        $.ajax({
            type: "POST",
            url: url,
            traditional :true,
            data: params,
            success: function(data){
                $('#result').val(data.data);
                console.log(data);
                return data;
            }
        });
    }



</script>
