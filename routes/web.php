<?php

use App\Classes\payment\pasargad\RSAKeyType;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TestingController;
use App\Models\User;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Classes\payment\pasargad\RSAProcessor;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/testing', [TestingController::class, 'testingByPostMethod']);

Route::get('/li', function(){
    if(Auth::guard('web')->check()){
        echo 'was logged in';
    }else{
        $user = User::where('id', 238856)->first();
        //Auth::guard('web')->login($user);
        if(Auth::guard('web')->check()){
            echo 'just logged in';
        }else{
            echo 'log in error';
        }
    }
});
Route::get('/ls', function(){
    if(Auth::guard('web')->check()){
        echo 'logged in';
    }else{
        echo 'logged out';
    }
});
Route::get('/lo', function(){
    if(Auth::guard('web')->check()){
        //Auth::guard('web')->logout();
        if(!Auth::guard('web')->check()){
            echo 'just logged out';
        }else{
            echo 'log out error';
        }
    }else{
        echo 'was logged out';
    }
});
Route::post('/check', function(){
    if(Auth::check()){
        echo 'already logged in';
    }else{
        $user = User::where('ex_user_id', 240308)->first();
        Auth::login($user);
        echo 'just logged in';
    }
});

Route::get('/check-location', function(){
    //
    $lat = 35.64671257866895;
    $lon = 51.24955331058874;
    $locations = [[35.7398594,51.6230822],[35.7401032,51.6225243],[35.7463729,51.598835],[35.7526422,51.5966034],[35.7573786,51.5849304],
            [35.7664328,51.5873337],[35.78231,51.5366936],[35.7919531,51.530664],[35.7933107,51.5385818],[35.8027784,51.537466],[35.8164908,51.5341187],
            [35.8196227,51.5087128],[35.8163516,51.5009022],[35.8226849,51.4917183],[35.8193443,51.484766],[35.8276955,51.4683723],[35.8269996,51.4637375],
            [35.8205971,51.4539528],[35.8217802,51.4423656],[35.8259558,51.4263153],[35.8186135,51.4130545],[35.8112706,51.3830566],[35.8065026,51.3745594],
            [35.7996807,51.3845587],[35.7950164,51.3844299],[35.7937632,51.378293],[35.8042751,51.3617706],[35.8031265,51.3588095],[35.7966176,51.3558912],
            [35.7963391,51.3414717],[35.7895511,51.334734],[35.7853736,51.3070107],[35.7724916,51.2896729],[35.7759038,51.2722492],[35.7689399,51.2545681],
            [35.7680346,51.2405777],[35.7617666,51.2380028],[35.7635077,51.2141418],[35.7656667,51.197834],[35.757309,51.1948299],[35.7558463,51.1869335],
            [35.7589806,51.1727715],[35.7613487,51.1589527],[35.7534432,51.1274099],[35.7484279,51.1306715],[35.7458505,51.1275816],[35.7373861,51.1206722],
            [35.730837,51.1251783],[35.724357,51.1300278],[35.7169705,51.1398125],[35.712162,51.1522579],[35.7079804,51.1667633],[35.7035198,51.1835861],
            [35.6835835,51.2333679],[35.6783546,51.2310505],[35.6748685,51.2309647],[35.6713823,51.2319088],[35.665525,51.2360287],[35.6614107,51.2428093],
            [35.6501128,51.2735367],[35.6417429,51.2926769],[35.6323258,51.3111305],[35.6264657,51.3198853],[35.615093,51.3297558],[35.6080453,51.3444328],
            [35.6093712,51.3559341],[35.6118833,51.366148],[35.603649,51.3704395],[35.6038583,51.3901806],[35.6015205,51.4180756],[35.5841065,51.4216805],
            [35.5842461,51.4227533],[35.5838971,51.4245558],[35.5833038,51.4256716],[35.5822218,51.427002],[35.5805116,51.4369583],[35.5814191,51.4467001],
            [35.5848046,51.4494467],[35.5904583,51.4560127],[35.5928663,51.4562702],[35.5950299,51.4557123],[35.597019,51.4541245],[35.6102783,51.4558411],
            [35.6146046,51.4682865],[35.6181631,51.4713764],[35.6046957,51.4997005],[35.6116041,51.5058804],[35.6210935,51.5021038],[35.6287679,51.5076828],
            [35.6375577,51.5076828],[35.6372089,51.5023613],[35.6413244,51.5010738],[35.6443237,51.5019321],[35.6440447,51.506052],[35.6461372,51.5091419],
            [35.6748685,51.5084553],[35.6689418,51.5236902],[35.670441,51.525836],[35.6911125,51.5049791],[35.6935523,51.4952374],[35.6943191,51.4966106],
            [35.7073531,51.5155792],[35.7118484,51.5167809],[35.7153677,51.5117168],[35.7219878,51.5176392],[35.7216394,51.5289688],[35.7234511,51.5377665],
            [35.7221969,51.5481091],[35.7243918,51.553688],[35.7246705,51.5633869],[35.7223014,51.5706396],[35.7220575,51.5864754],[35.7237995,51.5960455],
            [35.7242176,51.6026115],[35.7278061,51.6138554],[35.7398594,51.6230822]];
    
            $inside = false;
            for ($i = 0, $j = count($locations) - 1; $i < count($locations); $j = $i++) {
                echo 'i = ' . $i . ' and j = ' . $j . '<br />';
                $xi = $locations[$i][0]; $yi = $locations[$i][1];
                $xj = $locations[$j][0]; $yj = $locations[$j][1];
                
                $intersect = (($yi > $lon) != ($yj > $lon))
                    && ($lat < ($xj - $xi) * ($lon - $yi) / ($yj - $yi) + $xi);
                if ($intersect) $inside = !$inside;
            }
            
            if($inside){
                echo 'inside';
            }else{
                echo 'outside';
            }
});

Route::get('/check-test', function(){
    $a = jdate('w', time() + (3600 * 20));
    $dayOfTheWeek = 0;
    switch($a){
        case '۱':
            $dayOfTheWeek = 1;
            break;
        case '۲':
            $dayOfTheWeek = 2;
            break;
        case '۳':
            $dayOfTheWeek = 3;
            break;
        case '۴':
            $dayOfTheWeek = 4;
            break;
        case '۵':
            $dayOfTheWeek = 5;
            break;
        case '۶':
            $dayOfTheWeek = 6;
            break;
    }
    $allDays = DB::select("SELECT * FROM work_items ORDER BY day ASC, interval_id ASC");
    if(count($allDays) == 0){
        echo json_encode(array('status' => 'failed', 'message' => 'day not found'));
        exit();
    }
});

Route::get('/sign', function(){
    include('../app/Classes/payment/pasargad/RSAProcessor.php');
    $privateKey = '<RSAKeyValue><Modulus>u9QiaSVNXNcO2Ist3TJOPLKhNsV84FVNByhctGGWDpUhwrTx7x1gc+HM8PCnDTXHpmPzo08yQNqt0gjFPHjo1zitu4DIWilOmYeMNXRYYpn5mmZiBlCIkw7z7mc0kPoVLAdExk6FU0R8q8cy5xREbJpEfvLm+aj5Y+BtfPLokm0=</Modulus><Exponent>AQAB</Exponent><P>6iKdX28GXw5KjbthcCwnX9+NvFJuZ1Nn4uLUEAw0j/3eM3Zp5Fg97FJILBoXlU8pPQKj8h+YESD6UBp1eqHDQw==</P><Q>zV58S6HN/IVgDFxG72o13a57gpTBOV+KEFF88R8s5+mhDyLzD4s8Vf/IJfV3xcJeOckKMleMAYE9JlKYnTmAjw==</Q><DP>xZsRV0pNBkz5f0V2p0Wctb3n0dmAdJRgSY1HjYO/mQeaUbTPCnmvSZTodNBQtyNomqVv2RnxLgO3P4QVQrrkIQ==</DP><DQ>eZHEDFV1BVXCvK5nQ1RhHKAr9umt1BOtO+mxB19ICuSu9bHfpkTq65GlXmsHgqaDdrt+cLyIYV+q3iOoufGPGw==</DQ><InverseQ>ElTK3vHaTTYISddW9YQPOZlEWB7A/Xn3oV+y5SDPg3vAOegmhNGrE9qekJB1XaIgqCLTU6A71NXLhDOBrDHNcw==</InverseQ><D>jEsT9MN2+Gxt21KBzGFBzNaD0fxKnOk54qnELLtjMLs1f1BWEQs5OvUidajareRInsCzf3ytBYIRKPuCDvwktSyJ4MtYC+oxwTq9vo8NqKFyevYpK2gkwfSO+Ar5u3GZmh1ABy46C3QxzPH+lwxutnX7TMOVBs0HidYXQrX9R4U=</D></RSAKeyValue>';
    $pk = '<RSAKeyValue><Modulus>u9QiaSVNXNcO2Ist3TJOPLKhNsV84FVNByhctGGWDpUhwrTx7x1gc+HM8PCnDTXHpmPzo08yQNqt0gjFPHjo1zitu4DIWilOmYeMNXRYYpn5mmZiBlCIkw7z7mc0kPoVLAdExk6FU0R8q8cy5xREbJpEfvLm+aj5Y+BtfPLokm0=</Modulus><Exponent>AQAB</Exponent><P>6iKdX28GXw5KjbthcCwnX9+NvFJuZ1Nn4uLUEAw0j/3eM3Zp5Fg97FJILBoXlU8pPQKj8h+YESD6UBp1eqHDQw==</P><Q>zV58S6HN/IVgDFxG72o13a57gpTBOV+KEFF88R8s5+mhDyLzD4s8Vf/IJfV3xcJeOckKMleMAYE9JlKYnTmAjw==</Q><DP>xZsRV0pNBkz5f0V2p0Wctb3n0dmAdJRgSY1HjYO/mQeaUbTPCnmvSZTodNBQtyNomqVv2RnxLgO3P4QVQrrkIQ==</DP><DQ>eZHEDFV1BVXCvK5nQ1RhHKAr9umt1BOtO+mxB19ICuSu9bHfpkTq65GlXmsHgqaDdrt+cLyIYV+q3iOoufGPGw==</DQ><InverseQ>ElTK3vHaTTYISddW9YQPOZlEWB7A/Xn3oV+y5SDPg3vAOegmhNGrE9qekJB1XaIgqCLTU6A71NXLhDOBrDHNcw==</InverseQ><D>jEsT9MN2+Gxt21KBzGFBzNaD0fxKnOk54qnELLtjMLs1f1BWEQs5OvUidajareRInsCzf3ytBYIRKPuCDvwktSyJ4MtYC+oxwTq9vo8NqKFyevYpK2gkwfSO+Ar5u3GZmh1ABy46C3QxzPH+lwxutnX7TMOVBs0HidYXQrX9R4U=</D></RSAKeyValue>';
    /*$ContentType = 'Application/json';
    $MerchantCode = '4483845';
    $TerminalCode = '1664157';
    $InvoiceNumber= '13';
    $InvoiceDate = date('Y/m/d H:i:s');
    $Amount = 10000;
    $RedirectAddress = "https://honari.com";
    $TimeStamp = date('Y/m/d H:i:s');
    $Action = 1003;*/



    $time = time();
    $a = [
        'InvoiceNumber' => '16',
        'InvoiceDate' => date('Y/m/d H:i:s', $time),
        'TerminalCode' => 1664157, 
        'MerchantCode' => 4483845, 
        'Amount' => 10000,
        'RedirectAddress' => 'https://hoanri.com/bank-result',
        'Timestamp' => date('Y/m/d H:i:s', $time),
        'Action' => 1003
    ];
    $parameters = [
        'InvoiceNumber' => '15',
        'InvoiceDate' => date('Y/m/d H:i:s'),
        'TerminalCode' => 1664157,
        'MerchantCode' => 4483845,
        'Amount' => 10000,
        'RedirectAddress' => 'https://hoanri.com/bank-result',
        'Timestamp' => date('Y/m/d H:i:s'),
        'Action' => 1003,
    ];
    echo $parameters['Timestamp'];
    $oldParameters = [
        'MerchantCode' => 4483845,
        'TerminalCode' => 1664157, 
        'InvoiceNumber' => '15',
        'InvoiceDate' => date('Y/m/d H:i:s', $time),
        'Amount' => 10000,
        'RedirectAddress' => 'https://hoanri.com/bank-result',
        'Action' => 1003,
        'Timestamp' => date('Y/m/d H:i:s', $time),
    ];
    /*$signString = json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $RSAProcessor = new RSAProcessor($privateKey, 1);
    //$data = "#" . 4483845 . "#" . 1664157 . "#" . 15 . "#" . date('Y/m/d H:i:s', $time) . "#" . 1000 . "#" . 'https://hoanri.com/get' . "#" . 1003 . "#" . date('Y/m/d H:i:s', $time) . "#";
    $data = sha1($signString, true);
    $data = $RSAProcessor->sign($data);
    $sign = base64_encode($data);
    */
    $jsonData = json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $data = $jsonData;
    $processor = new RSAProcessor($pk, RSAKeyType::XMLString);
    $data = sha1($data, true);
    $data = $processor->sign($data);
    $sign = base64_encode($data);

    /*$ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, 'https://pep.shaparak.ir/Api/v1/Payment/GetToken');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $headers = [
            'Content-Type: application/json',
            'Sign: ' . $sign,
            'Content-Length: ' . strlen($jsonData)
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec ($ch);
    curl_close ($ch);
    print_r($response);*/

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, 'https://pep.shaparak.ir/Api/v1/Payment/GetToken');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Sign: ' . $sign,
    'Content-Length: ' . strlen($jsonData)
    ));
    $response = curl_exec ($ch);
    var_dump($response);
    curl_close ($ch);
});

Route::get('/bank-result', function(){
    $data = [
        'transactionReferenceID' => '637744001835419255'
    ];
    $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, 'https://pep.shaparak.ir/Api/v1/Payment/CheckTransactionResult');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    //'Sign: ' . $sign,
    'Content-Length: ' . strlen($jsonData)
    ));
    $response = curl_exec ($ch);
    var_dump($response);
    curl_close ($ch);
});