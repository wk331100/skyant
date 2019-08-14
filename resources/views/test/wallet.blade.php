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
            <h5>通用参数</h5>
            <hr>
            <div class="col-md-3"><span>Private Key: </span><input type="text" id="key" value="SkyAntIsAVeryGoodProductionThatMadeByASpecialTeam" class="form-control"></div>
            <div class="col-md-3"><span>Coin: </span><input type="text" id="coin" value="bz" class="form-control"></div>
            <div class="col-md-3"><span>Number: </span><input type="text" id="number" value="1" class="form-control "></div>
            <div class="col-md-3"><span>Address: </span><input type="text" id="address" value="testAddressBz" class="form-control"></div>
            <div class="col-md-3 m-t-20"><span>Confirm: </span><input type="text" id="confirm" value="1" class="form-control"></div>
            <div class="col-md-3 m-t-20"><span>Txid: </span><input type="text" id="txid" value="txid1004" class="form-control"></div>
        </div>
        <div class="input-box clearfix m-b-40">
            <h5>操作</h5>
            <hr>
            <div class="col-md-12 m-t-20">
                <button class="btn btn-default" onclick="transferIn();">钱包充币</button>
                <button class="btn btn-default" onclick="confirm();">充币确认</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<script type="text/javascript">
    
    function transferIn() {
        var time = new Date().getTime();
        time = time.toString().substr(0,10);
        var sign = makeSign(time);

        var params = {
            'address' : $('#address').val(),
            'coin' : $('#coin').val(),
            'confirm' : $('#confirm').val(),
            'number' : $('#number').val(),
            'private_key' :  $('#key').val(),
            'timestamp' : time,
            'txid' : $('#txid').val(),
            'sign' : sign
        };

        var url = '/api/wallet/transferIn';

        console.log(params);
        $res = ajax(url,params);
        console.log($res);
    }
    
    function confirm() {
        var time = new Date().getTime();
        time = time.toString().substr(0,10);
        var sign = makeSign(time);

        var params = {
            'address' : $('#address').val(),
            'coin' : $('#coin').val(),
            'confirm' : $('#confirm').val(),
            'number' : $('#number').val(),
            'private_key' :  $('#key').val(),
            'timestamp' : time,
            'txid' : $('#txid').val(),
            'sign' : sign
        };

        var url = '/api/wallet/confirm';

        console.log(params);
        $res = ajax(url,params);
        console.log($res);
    }
    
    
    function makeSign(time) {
        var arr = {
            'address' : $('#address').val(),
            'coin' : $('#coin').val(),
            'confirm' : $('#confirm').val(),
            'number' : $('#number').val(),
            'private_key' :  $('#key').val(),
            'timestamp' : time,
            'txid' : $('#txid').val(),
        };
        str = JSON.stringify(arr);
        return md5(str);
    }
    
    
    



    function ajax(url, params) {
        $.ajax({
            type: "POST",
            url: url,
            traditional :true,
            data: params,
            success: function(data){
                return data;
            }
        });
    }



</script>
