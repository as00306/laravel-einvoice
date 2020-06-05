<?php

namespace Tests;

use DQ\EInvoice\EInvoiceService;
use SDK\EcpayInvoice;
use Mockery;

class EInvoiceServiceTest extends \Orchestra\Testbench\TestCase
{
    public function testEinvoice()
    {
        // 產生測試用自訂訂單編號
        $relateNumber = 'ECPAY'. date('YmdHis');

        // 發票品項
        $items = [
            ['ItemName' => '商品名稱一', 'ItemCount' => 1, 'ItemWord' => '批', 'ItemPrice' => 100, 'ItemTaxType' => 1, 'ItemAmount' => 100, 'ItemRemark' => '商品備註一'],
            ['ItemName' => '商品名稱二', 'ItemCount' => 1, 'ItemWord' => '批', 'ItemPrice' => 150, 'ItemTaxType' => 1, 'ItemAmount' => 150, 'ItemRemark' => '商品備註二'],
            ['ItemName' => '商品名稱二', 'ItemCount' => 1, 'ItemWord' => '批', 'ItemPrice' => 250, 'ItemTaxType' => 1, 'ItemAmount' => 250, 'ItemRemark' => '商品備註三'],
        ];

        // 發票資訊
        $infos = [
            'RelateNumber' => $relateNumber,
            'CustomerID' => '',
            'CustomerIdentifier' => '',
            'CustomerName' => '',
            'CustomerAddr' => '',
            'CustomerPhone' => ' +123',
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
        
        $service = $this->getServiceMock(['Check_Out' => ['RtnCode' => 1]]);
        $response = $service->einvoice($infos);

        // 回應 1 代表成功
        $this->assertEquals(1, $response['RtnCode']);
    }

    public function testSearch()
    {
        $relateNumber = 'ECPAY201809210520511291807010';

        $infos = [
            'RelateNumber' => $relateNumber,
        ];

        //mock
        $service = $this->getServiceMock(['Check_Out' => ['RtnCode' => 1, 'IIS_Relate_Number' => $relateNumber]]);
        $response = $service->search($infos);

        $this->assertEquals(1, $response['RtnCode']);
        $this->assertEquals($relateNumber, $response['IIS_Relate_Number']);
    }

    public function testVoid()
    {
        $InvoiceNumber = 'aa12345671';
        $infos = [
            'InvoiceNumber' => $InvoiceNumber,
            'Reason' => 'aaa'
        ];

        //mock
        $service = $this->getServiceMock([], ['Check_Out' => 'Error：無發票號碼資料']);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error：無發票號碼資料');

        $service->void($infos);
    }

    public function testException()
    {
        $infos = [];

        //mock
        $service = $this->getServiceMock([], ['Check_Out' => '4:RelateNumber is required.']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('合作特店自訂編號 為必填');

        $service->search($infos);
    }

    private function getServiceMock(array $methods, $exception = null)
    {
        $url = 'https://einvoice-stage.ecpay.com.tw/';
        
        $mock = Mockery::mock('EcpayInvoice', $methods);
        $mock->Invoice_Url = $url;
        if ($exception) {
            $key = key($exception);
            $mock->shouldReceive(key($exception))->andThrow(\Exception::class, $exception[$key]);
        }

        return new EInvoiceService([],  $mock);
    }
}
