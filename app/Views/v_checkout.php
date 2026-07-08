<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-6">
        <?= form_open('buy', 'class="row g-3"') ?>

        <?= form_hidden('username', session()->get('username')) ?>

        <?= form_input([
            'type' => 'hidden', 
            'name' => 'total_harga', 
            'id' => 'total_harga'
        ]) ?>

        <div class="col-12">
            <?= form_label('Nama', 'nama', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'     => 'nama',
                'id'       => 'nama',
                'class'    => 'form-control',
                'value'    => session()->get('username'),
                'readonly' => true
            ]) ?>
        </div>
        <div class="col-12">
            <?= form_label('Alamat', 'alamat', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'  => 'alamat',
                'id'    => 'alamat',
                'class' => 'form-control'
            ]) ?>
        </div> 
        <div class="col-12"> 
            <?= form_label('Kelurahan', 'kelurahan', ['class' => 'form-label']) ?>
            <?= form_dropdown('kelurahan', [], '', ['id' => 'kelurahan', 'class' => 'form-control']) ?>
        </div>
        <div class="col-12"> 
            <?= form_label('Layanan', 'layanan', ['class' => 'form-label']) ?> 
            <?= form_dropdown('layanan', [], '', ['id' => 'layanan', 'class' => 'form-control']) ?>
        </div>
        <div class="col-12">
            <?= form_label('Ongkir', 'ongkir', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'     => 'ongkir',
                'id'       => 'ongkir',
                'class'    => 'form-control',
                'readonly' => true
            ]) ?>
        </div>
        <div class="col-12">
    <?= form_label('Kode Voucher', 'voucher_code', ['class' => 'form-label']) ?>

    <?= form_input([
        'name' => 'voucher_code',
        'id' => 'voucher_code',
        'class' => 'form-control',
        'placeholder' => 'PROMO2025 / PROMO2026 / AKHIRTAHUN'
    ]) ?>
</div>
        <div class="col-12">
            <?= form_submit(
                'submit',
                'Buat Pesanan',
                ['class' => 'btn btn-primary']
            ) ?>
        </div>

        <?= form_close() ?> 
    </div>

    <div class="col-lg-6">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Jumlah</th>
                    <th scope="col">Sub Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($items)) :
                    foreach ($items as $index => $item) :
                ?>
                        <tr>
                            <td><?= $item['name'] ?></td>
                            <td><?= number_to_currency($item['price'], 'IDR') ?></td>
                            <td><?= $item['qty'] ?></td>
                            <td><?= number_to_currency($item['price'] * $item['qty'], 'IDR') ?></td>
                        </tr>
                <?php
                    endforeach;
                endif;
                ?>
                <tr>
                    <td colspan="2"></td>
                    <td>Subtotal</td>
                    <td><?= number_to_currency($total, 'IDR') ?></td>
                </tr>
                
               <tr>
    <td colspan="2"></td>
    <td>Biaya Jasa</td>
    <td id="biaya-jasa">Rp 0</td>
</tr>

<tr>
    <td colspan="2"></td>
    <td>Diskon Voucher</td>
    <td id="diskon-voucher">Rp 0</td>
</tr>

<tr>
    <td colspan="2"></td>
    <td>Free Mouse</td>
    <td id="free-mouse">Rp 0</td>
</tr>

                <tr>
                    <td colspan="2"></td>
                    <td><strong>Grand Total</strong></td>
                    <td><strong><span id="total">IDR 0</span></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('script') ?>
<script>
$(document).ready(function () {

    let ongkir = 0;
    let subtotal = <?= isset($total) ? $total : 0 ?>;

    $("#voucher_code").keyup(function () {
        hitungTotal();
    });

    // Panggil saat halaman pertama dibuka
    hitungTotal();

    function hitungTotal() {

    let biayaJasa = 0;

    if(subtotal <= 10000000){

        biayaJasa = subtotal * 0.01;

    }else{

        biayaJasa = subtotal * 0.02;

    }

    let voucher = $("#voucher_code").val().toUpperCase();

    let diskonVoucher = 0;

    if(voucher=="PROMO2025"){

        diskonVoucher = subtotal * 0.10;

    }else if(voucher=="PROMO2026"){

        diskonVoucher = subtotal * 0.15;

    }else if(voucher=="AKHIRTAHUN"){

        diskonVoucher = subtotal * 0.25;

    }

    let freeMouse = subtotal>15000000 ? 150000 : 0;

    let grandTotal = subtotal + biayaJasa + ongkir - diskonVoucher - freeMouse;

    $("#ongkir").val(ongkir);

    $("#biaya-jasa").text("Rp "+biayaJasa.toLocaleString('id-ID'));

    $("#diskon-voucher").text("-Rp "+diskonVoucher.toLocaleString('id-ID'));

    $("#free-mouse").text("-Rp "+freeMouse.toLocaleString('id-ID'));

    $("#total").text("Rp "+grandTotal.toLocaleString('id-ID'));

    $("#total_harga").val(grandTotal);

}

    // Select2 untuk daerah tujuan
    $('#kelurahan').select2({
        placeholder: 'Cari daerah tujuan',
        minimumInputLength: 3,
        ajax: {
            url: '<?= site_url('ajax/destinations') ?>',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return data;
            },
            cache: true
        } 
    });

    // Ketika kelurahan diganti
    $("#kelurahan").on('change', function () {
        let id_kelurahan = $(this).val();

        $("#layanan").empty();
        ongkir = 0;
        hitungTotal(); 

        $.ajax({
            url: "<?= site_url('ajax/costs') ?>", 
            dataType: "json",
            data: { destination: id_kelurahan },
            success: function (data) { 
                if(data && data.length > 0) {
                    data.forEach(function (item) {
                        $("#layanan").append(
                            $('<option>', {
                                value: item.cost,
                                text: `${item.description} (${item.service}) : estimasi ${item.etd}`
                            })
                        );
                    });
                    
                    // Ambil nilai ongkir dari opsi pertama yang baru dimasukkan
                    ongkir = parseInt($("#layanan").val()) || 0;
                } else {
                    $("#layanan").append($('<option>', { value: 0, text: 'Layanan tidak tersedia' }));
                    ongkir = 0;
                }
                hitungTotal();
            }
        });
    });

    // Ketika pilihan layanan/ongkir berubah
    $("#layanan").on('change', function() {
        ongkir = parseInt($(this).val()) || 0;
        hitungTotal();
    }); 
});
</script>
<?= $this->endSection() ?>