<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RajaOngkirService;
use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;

class TransaksiController extends BaseController
{
    protected $cart;
    protected $transactionModel;
protected $transactionDetailModel;

public function __construct()
{
    helper(['number', 'form']);
    $this->cart = service('cart');
    $this->transactionModel = new TransactionModel();
$this->transactionDetailModel = new TransactionDetailModel(); 
}
    public function index()
{  
    $data = [
        'items' => $this->cart->contents(),
        'total' => $this->cart->total()  
    ];

    return view('v_keranjang', $data);
}

public function cart_add()
{
	$this->cart->insert([
	    'id'      => $this->request->getPost('id'),
	    'qty'     => 1,
	    'price'   => $this->request->getPost('harga'),
	    'name'    => $this->request->getPost('nama'),
	    'options' => [
	        'foto' => $this->request->getPost('foto')
	    ]
	]);
	
	session()->setFlashdata(
	    'success',
	    'Produk berhasil ditambahkan ke keranjang. 
	    <a href="' . base_url('keranjang') . '">Lihat</a>'
	);
	
	return redirect()->to(base_url('/'));
} 
public function cart_edit()
{
    $i = 1;
    foreach ($this->cart->contents() as $item) {
        $qty = $this->request->getPost('qty' . $i++);

        $this->cart->update([
            'rowid' => $item['rowid'],
            'qty'   => $qty
        ]);
    }

    session()->setFlashdata(
        'success',
        'Keranjang berhasil diperbarui'
    );

    return redirect()->to(base_url('keranjang'));
}
public function cart_delete($rowid)
{
    $this->cart->remove($rowid);

    session()->setFlashdata(
        'success',
        'Produk berhasil dihapus dari keranjang'
    );

    return redirect()->to(base_url('keranjang'));
}
public function cart_clear()
{
    $this->cart->destroy();

    session()->setFlashdata(
        'success',
        'Keranjang berhasil dikosongkan'
    );

    return redirect()->to(base_url('keranjang'));
}
public function checkout()
{  
    $data = [
        'items' => $this->cart->contents(),
        'total' => $this->cart->total()
    ];

    return view('v_checkout', $data);
}
public function destinations()
{
    $search = $this->request->getGet('q'); 

    $service = new RajaOngkirService();
$response = $service->getDestination($search);

$results = [];
$data = $response['data'] ?? [];

foreach ($data as $item) {
    $results[] = [
        'id'   => $item['id'],
        'text' => $item['label']
    ];
}

    return $this->response->setJSON([
        'results' => $results
    ]);
}
public function costs()
{
    $origin = '64999';
    $destination = $this->request->getGet('destination');
    $weight = '1000';
    $courier = 'jne'; 

    $service = new RajaOngkirService();
    $response = $service->getCost($origin, $destination, $weight, $courier);

    $results = [];
    $data = $response['data'] ?? [];

    foreach ($data as $item) {
        $results[] = [
            'service'     => $item['service'],
            'description' => $item['description'],
            'cost'        => $item['cost'],
            'etd'         => $item['etd']
        ];
    }

    return $this->response->setJSON($results);
}
public function buy()
{ 
    helper('diskon');
    $cartItems = $this->cart->contents();

    if (empty($cartItems)) {
        return redirect()->back();
    }

    $db = \Config\Database::connect();
    $db->transStart(); 

    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['qty'] * $item['price'];
    }

    $ongkir = (int) $this->request->getPost('ongkir');

    // 1. Load helper diskon & hitung diskon dari subtotal belanjaan
    $voucher = strtoupper(trim($this->request->getPost('voucher_code')));

$biayaJasa = hitung_biaya_jasa($subtotal);

$diskonVoucher = hitung_diskon_voucher($subtotal, $voucher);

$freeMouse = hitung_free_mouse($subtotal);

    // 2. Susun data transaksi (Tambahkan field diskon & kurangi total_harga dengan diskon)
    $grandTotal = ($subtotal + $biayaJasa + $ongkir)
            - $diskonVoucher
            - $freeMouse;

$transaction = [

    'username'         => $this->request->getPost('username'),
    'alamat'           => $this->request->getPost('alamat'),

    'ongkir'           => $ongkir,

    'biaya_jasa'       => $biayaJasa,
    'voucher_code'     => $voucher,
    'diskon_voucher'   => $diskonVoucher,
    'free_mouse'       => $freeMouse,

    'total_harga'      => $grandTotal,

    'status'           => 0

];

    // insert transaction
    if (!$this->transactionModel->insert($transaction)) {
        $db->transRollback();
        return redirect()->back()->with('error', 'Gagal membuat transaksi');
    }

    $transactionId = $this->transactionModel->getInsertID();

    // insert transaction detail
    foreach ($cartItems as $item) {
        $this->transactionDetailModel->insert([
            'transaction_id' => $transactionId,
            'product_id'     => $item['id'],
            'jumlah'         => $item['qty'],
            'diskon'         => $diskonVoucher, // Ini diskon per barang (biarkan 0 dulu jika belum dipakai)
            'subtotal_harga' => $item['qty'] * $item['price'] 
        ]);
    }

    $db->transComplete();

    if (!$db->transStatus()) {
        return redirect()->back()->with('error', 'Gagal membuat transaksi');
    }

    // hapus session keranjang belanja 
    $this->cart->destroy();
    return redirect()->to(base_url());
}
public function history()
{
    $username = session()->get('username'); 
 
    $transactions = $this->transactionModel->where('username', $username)->findAll();
    $transactionIds = array_column($transactions, 'id');

    $products = $this->transactionDetailModel->getProductsByTransactionIds($transactionIds);

    $data = [
        'username'      => $username,
        'transactions'  => $transactions,
        'products'      => $products
    ]; 

    return view('v_history', $data);
}
public function store()
    {
        // 1. Load helper diskon yang sudah dibuat
        helper('diskon');

        $subtotal = $this->request->getPost('subtotal'); // Ambil nilai subtotal barang
        $ongkir = $this->request->getPost('ongkir');     // Ambil nilai ongkir (misal: 40000)

        // 2. Hitung diskon menggunakan fungsi dari helper
        $dataDiskon = hitung_diskon($subtotal);
        $nilaiDiskon = $dataDiskon['nilai_diskon'];

        // 3. Kalkulasi Grand Total
        $grandTotal = $this->request->getPost('total_harga');

        // 4. Simpan ke database via Model
        $model = new TransactionModel();
        $model->save([
            'nama'        => $this->request->getPost('nama'),
            'alamat'      => $this->request->getPost('alamat'),
            'subtotal'    => $subtotal,
            'diskon'      => $nilaiDiskon, // Menyimpan nominal diskon
            'ongkir'      => $ongkir,
            'grand_total' => $grandTotal,
        ]);

        return redirect()->to('/checkout/success');
    }
}
