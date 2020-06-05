<?php

namespace DQ\EInvoice;

use SDK\EcpayInvoice;

class EInvoiceService
{
    protected $ecpayInvoice;
    const SEARCH = [
        'METHOD' => 'INVOICE_SEARCH',
        'URL' => 'Query/Issue'
    ];
    const INVOICE = [
        'METHOD' => 'INVOICE',
        'URL' => 'Invoice/Issue'
    ];
    const VOID = [
        'METHOD' => 'INVOICE_VOID',
        'URL' => 'Invoice/IssueInvalid'
    ];

    // 折讓
    const ALLOWANCE = [
        'METHOD' => 'ALLOWANCE',
        'URL' => 'Invoice/Allowance'
    ];

    const ALLOWANCE_VOID = [
        'METHOD' => 'ALLOWANCE_VOID',
        'URL' => 'Invoice/AllowanceInvalid'
    ];

    public function __construct(array $config, $handler = null)
    {
        if ($handler) {
            return $this->ecpayInvoice = $handler;
        }

        $this->ecpayInvoice = new EcpayInvoice;

        // 寫入基本介接參數
        $this->ecpayInvoice->MerchantID = $config['MerchantID'];
        $this->ecpayInvoice->HashKey = $config['HashKey'];
        $this->ecpayInvoice->HashIV = $config['HashIV'];
        $this->ecpayInvoice->Invoice_Url = $config['Invoice_Url'];
    }

    public function einvoice(array $infos)
    {
        $infos = $this->formatInfos($infos);

        return $this->sendRequest($infos, self::INVOICE);
    }

    public function search(array $infos)
    {
        return $this->sendRequest($infos, self::SEARCH);
    }

    public function void(array $infos)
    {
        return $this->sendRequest($infos, self::VOID);
    }

    public function allowance(array $infos)
    {
        return $this->sendRequest($infos, self::ALLOWANCE);
    }

    public function allowanceVoid(array $infos)
    {
        return $this->sendRequest($infos, self::ALLOWANCE_VOID);
    }

    private function sendRequest(array $infos, $method)
    {
        $this->ecpayInvoice->Invoice_Method = $method['METHOD'];
        $this->ecpayInvoice->Invoice_Url = $this->ecpayInvoice->Invoice_Url.$method['URL'];

        foreach ($infos as $field => $value) {
            $this->ecpayInvoice->Send[$field] = $value;
        }
        try {
            $response = $this->ecpayInvoice->Check_Out();
        } catch (\Exception $e) {
            throw new \Exception($this->translate($e->getMessage()));
        }

        if (!isset($response['RtnCode'])) {
            throw new \Exception("發票參數設定錯誤，請重新設定");
        }

        if ($response['RtnCode'] != 1) {
            $message = "Error：" . array_get($response, 'RtnMsg', 'unknown')
            . " email: " . array_get($infos, 'CustomerEmail')
            . " phone: " . array_get($infos, 'CustomerPhone')
            . " amount: " . array_get($infos, 'SalesAmount');

            \Log::error($message);

            throw new \Exception(array_get($response, 'RtnMsg'));
        }

        return $response;
    }

    /**
     * 修正輸入訊息
     *
     * @param array $infos 輸入的資料若有電話號碼則修正。
     * @return array
     */
    private function formatInfos(array $infos)
    {
        $customerPhone = array_get($infos, 'CustomerPhone');

        // 清除手機號碼前後空白與去除+號
        if ($customerPhone) {
            $infos['CustomerPhone'] = trim(str_replace('+', '', $customerPhone));
        }

        // 檢查有無 customerName，若無為空白
        $customerName = array_get($infos, 'CustomerName');
        if (empty($customerName)) {
            $infos['CustomerName'] = ' ';
        }

        return $infos;
    }

    /**
     * 轉換錯誤訊息
     *
     * @param string $message EcpayInvoice內的arErrors
     * @return string
     */
    private function translate($message)
    {
        $result = [];
        $messageArray = explode('<br>', $message);
        $translateMessage = $this->translateMessages();

        foreach ($messageArray as $message) {
            $result[] = array_get($translateMessage, $message, $message);
        }

        return implode('<br>', $result);
    }

    /**
     * 錯誤格式轉換
     *
     * @return array
     */
    private function translateMessages()
    {
        return [
            '1000001 CheckMacValue verify fail.' => 'CheckMacValue 驗證失敗',
            'Invoice_Method is required.' => '發票使用方法為必填',
            'MerchantID is required.' => 'MerchantID 為必填',
            'MerchantID max langth as 10.' => 'MerchantID 長度不得大於30',
            'HashKey is required.' => 'HashKey 為必填',
            'HashIV is required.' => 'HashIV 為必填',
            'Invoice_Url is required.' => '發票網頁連結 為必填',
            '4:RelateNumber is required.' => '合作特店自訂編號 為必填',
            '4:RelateNumber max langth as 30.' => '合作特店自訂編號 長度不得大於30',
            '5:CustomerID is required.' => '載具客戶代號 為必填',
            '5:CustomerID max langth as 20.' => '載具客戶代號 長度不得大於20',
            '5:Invalid CustomerID.' => '不符合格式的 載具客戶代號',
            '6:CustomerIdentifier length should be 8.' => '統一編號 長度應該要符合8碼',
            '7:CustomerName max length as 30.' => '客戶名稱 長度不得大於30',
            "7:CustomerName is required." => '客戶名稱 為必填',
            "8:CustomerAddr is required." => '客戶地址 為必填',
            "8:CustomerAddr max length as 100." => '客戶地址 長度不得大於100',
            "9:CustomerPhone max length as 20." => '客戶電話 長度不得大於20',
            '9:Invalid CustomerPhone.' => '不符合格式的 客戶電話',
            "10:CustomerEmail max length as 80." => '客戶email 長度不得大於80',
            '10:Invalid CustomerEmail Format.' => '不符合格式的 客戶email',
            "9-10:CustomerPhone or CustomerEmail is required." => '客戶電話 或是 客戶email 為必填',
            "11:ClearanceMark max length as 1." => '通關方式 長度不得大於1',
            "11:ClearanceMark is required." => '通關方式 為必填',
            "11:Please remove ClearanceMark." => '請移除 通關方式',
            "12:Invalid Print." => '不符合格式的 紙本發票列印註記',
            "12:Donation Print should be No." => '若有捐贈發票 紙本發票列印註記要勾選不索取',
            "12:CustomerIdentifier Print should be Yes." => '若有統一編號 紙本發票列印註記要勾選索取',
            "13:Invalid Donation." => '不符合格式的 捐贈註記',
            "13:CustomerIdentifier Donation should be No." => '若有統一編號 捐贈註記要勾選不捐贈',
            "14:Invalid LoveCode." => '不符合格式的 愛心碼',
            "14:Please remove LoveCode." => '請移除 愛心碼',
            "15:Invalid CarruerType." => '不符合格式的 載具類別',
            "16:Please remove CarruerNum." => '請移除 載具編號',
            "16:Invalid CarruerNum." => '不符合格式的 載具編號',
            "17:TaxType is required." => '課稅類別 為必填',
            "17:Invalid TaxType." => '不符合格式的 課稅類別',
            "18:SalesAmount is required." => '發票總金額(含稅) 為必填',
            '20-25:Items is required.' => '商品 為必填',
            '20:Invalid ItemName.' => '不符合格式的 商品名稱',
            '21:Invalid ItemCount.' => '不符合格式的 商品數量',
            '22:Invalid ItemWord.' => '不符合格式的 商品單位',
            '23:Invalid ItemPrice.' => '不符合格式的 商品價格',
            '24:Invalid ItemTaxType.' => '不符合格式的 商品課稅別',
            '25:Invalid ItemAmount.' => '不符合格式的 商品合計',
            '143:Invalid ItemRemark.' => '不符合格式的 商品備註',
            '21:Invalid ItemCount.' => '不符合格式的 商品數量',
            '22:ItemWord max length as 6.' => '商品單位 長度不得大於6',
            '23:Invalid ItemPrice.A' => '不符合格式的 商品價格(價格應該為數字)',
            '25:Invalid ItemAmount.B' => '不符合格式的 商品合計(合計應該為數字)',
            '143:ItemRemark max length as 40.' => '商品備註 長度不得大於40',
            "27:Invalid InvType." => '不符合格式的 字軌類別',
            "29:Invalid VatType." => '不符合格式的 商品單價是否含稅',
            "30:Invalid DelayFlagType." => '不符合格式的 延遲註記',
            "31:DelayDay should be 1 ~ 15." => '若為延遲開立時，延遲天數須介於1至15天內',
            "31:DelayDay should be 0 ~ 15." => '若為觸發開立時，延遲天數須介於0至15天內',
            '33:Tsr is required.' => '付款完成觸發或延遲開立發票的交易單號 為必填',
            '33:Tsr max length as 30.' => '付款完成觸發或延遲開立發票的交易單號 長度不得大於30',
            "34:Invalid PayType." => '不符合格式的 交易類別(若是延遲開立發票為2, 觸發開立發票為3)',
            '35:PayAct is required.' => '交易類別名稱 為必填',
            '37:InvoiceNo is required.' => '發票號碼 為必填',
            '37:InvoiceNo length as 10.' => '發票號碼 長度不得大於10',
            "38:Invalid AllowanceNotifyType." => '不符合格式的 通知類別',
            '39:Invalid Email Format.' => '不符合格式的 通知信箱',
            "39:NotifyMail is required." => '通知信箱 為必填',
            '40:Invalid NotifyPhone.' => '不符合格式的 通知電話',
            '40:NotifyPhone max length as 20.' => '通知電話 長度不得大於20',
            "40:NotifyPhone is required." => '通知電話 為必填',
            "39-40:NotifyMail or NotifyPhone is required." => '通知信箱 或 通知電話 則一必填',
            "39-40:NotifyMail And NotifyPhone is required." => '通知信箱 及 通知電話 皆為必填',
            "39-40:Please remove NotifyMail And NotifyPhone." => '請移除 通知信箱 及 通知電話',
            "41:AllowanceAmount is required." => '折讓單總金額(含稅) 為必填',
            "42:InvoiceNumber is required." => '發票號碼 為必填',
            '42:InvoiceNumber length as 10.' => '發票號碼 長度不得大於10',
            "43:Reason is required." => '作廢原因 為必填',
            "43:Reason max length as 20." => '作廢原因 長度不得大於20',
            "44:AllowanceNo is required." => '折讓編號 為必填',
            '44:AllowanceNo length as 16.' => '折讓編號 長度不得大於16',
            '45:Invalid Email Format.' => '不符合格式的 通知信箱',
            '46:Invalid Phone.' => '不符合格式的 通知電話',
            "46:Phone max length as 20." => '通知電話 長度不得大於20',
            "46:Phone is required." => '通知電話 為必填',
            "45-46:NotifyMail or Phone is required." => '通知信箱 或 通知電話 則一必填',
            "45-46:NotifyMail and Phone is required." => '通知信箱 及 通知電話 皆為必填',
            "47:Notify is required." => '發送發票通知 為必填',
            "48:InvoiceTag is required." => '發送發票內容類型 為必填',
            "49:Notified is required." => '發送對象 為必填',
            "50:BarCode max length as 8." => '手機條碼 長度不得大於8',
            "51:LoveCode max length as 7." => '愛心碼 長度不得大於7',
        ];
    }
}
