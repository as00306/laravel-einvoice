# 綠界發票

## 安裝
在 `composer.json` 的 `repositories` 區段加上一組 repository

~~~
// composer.json
    "repositories": [
        {
            "type": "git",
            "url": "ssh://git@git.dunqian.tw:30001/itrd/pkg-ecpay-einvoice.git"
        }
    ],
~~~

透過 composer 安裝此套件

~~~
composer require dq/e-invoice:^1.0
~~~

### 設定



`config/services.php` 加入綠界發票所需參數

~~~
 'einvoice' => [
        'Invoice_Url' => env('INVOICE_URL'),
        'MerchantID' => env('INVOICE_MERCHANTID'),
        'HashKey' => env('INVOICE_HASHKEY'),
        'HashIV' => env('INVOICE_HASHIV'),
    ]
~~~   
    
在 `.env` 加入正式環境的主機路徑

~~~
//  範例為綠界提供的測試帳號資訊

INVOICE_URL=https://einvoice-stage.ecpay.com.tw/
INVOICE_MERCHANTID=2000132
INVOICE_HASHKEY=ejCk326UnaZWKisg
INVOICE_HASHIV=q9jcZX8Ib9LM8wYk
~~~

### 服務
透過服務開立發票

~~~
use DQ\EInvoice\EInvoiceService;


$config = config('services.einvoice');


app()->when(EInvoiceService::class)->needs('$config')->give($config);

$pkg = app(EInvoiceService::class);


$RelateNumber = 'ECPAY'. date('YmdHis') . rand(1000000000, 2147483647) ; // 產生測試用自訂訂單編號

// 發票品項
$items = [
            ['ItemName' => '商品名稱一', 'ItemCount' => 1, 'ItemWord' => '批', 'ItemPrice' => 100, 'ItemTaxType' => 1, 'ItemAmount' => 100, 'ItemRemark' => '商品備註一'],
            ['ItemName' => '商品名稱二', 'ItemCount' => 1, 'ItemWord' => '批', 'ItemPrice' => 150, 'ItemTaxType' => 1, 'ItemAmount' => 150, 'ItemRemark' => '商品備註二'],
            ['ItemName' => '商品名稱二', 'ItemCount' => 1, 'ItemWord' => '批', 'ItemPrice' => 250, 'ItemTaxType' => 1, 'ItemAmount' => 250, 'ItemRemark' => '商品備註三'],
        ];

// 發票資訊
$infos = [
    'RelateNumber' => $RelateNumber,
    'CustomerID' => '',
    'CustomerIdentifier' => '',
    'CustomerName' => '',
    'CustomerAddr' => '',
    'CustomerPhone' => '',
    'CustomerEmail' => 'test@localhost.com',
    'ClearanceMark' => '',
    'Print' => 0,
    'Donation' => 2,
    'LoveCode' => '',
    'CarruerType' => '',
    'CarruerNum' => '',
    'TaxType' => 1,
    'SalesAmount' => 500,
    'InvoiceRemark' => '備註',
    'InvType' => '07',
    'vat' => '',
    'Items' => $items,
];


// 發票開立
$response = $pkg->einvoice($infos);

$infos = [
            'InvoiceNumber' => '發票號碼',
            'Reason' => '開錯了',
];

// 發票作廢
$pkg->void([info]);

// 發票查詢
$pkg->search();


// 發票折讓
// 要折讓的商品資訊
$items = [[
    'ItemName' => '商品名稱一',
    'ItemCount' => 1,
    'ItemWord' => '批',
    'ItemPrice' => 50,
    'ItemTaxType' => 1,
    'ItemAmount' => 50,
]];

// 折讓資訊
$infos = [
    'CustomerName' => '',
    'InvoiceNo' => 'JQ10002173',
    'AllowanceNotify' => 'E',
    'NotifyMail' => 'tomer.rd02@gmail.com',
    'NotifyPhone' => '',
    'AllowanceAmount' => 50,
    'Items' => $items,
];


$pkg->allowance($infos);



// 發票折讓作廢

$infos = [
    'InvoiceNo' => '發票號碼',
    'Reason' => '作廢原因',
    'AllowanceNo' => '折讓單號'
 ]

$pkg->allowanceVoid($infos);




~~~

### 服務二

~~~

app.providers 加入  DQ\EInvoice\ServiceProvider::class,

設定    config(['services.einvoice' => $einvoice]);  後

使用  app('einvoice')->einvoice($infos);   開發票

~~~



