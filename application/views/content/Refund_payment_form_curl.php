<?php $this->load->view('theme/header') ?>
<div class="container">

    <div class="starter-template">
        <h1>PayPal Refund Payment</h1>
        <p class="lead">Refund Now</p>
    </div>

    <div class="contact-form">

        <p class="notice error"><?= $this->session->flashdata('error_msg') ?></p><br/>
        <p class="notice error"><?= $this->session->flashdata('success_msg') ?></p><br/>

        <form method="post" class="form-horizontal" role="form" action="<?= base_url() ?>paypal/refund_payment_curl">
            <div class="form-group">
                <label class="label label-primary">refund_amount</label>
                <input class="form-control" title="refund_amount" name="refund_amount" type="text" value="14.00">
            </div>
            <div class="form-group">
                <label class="label label-primary">Transaction Id / Sale Id</label>
                <input class="form-control" title="sale_id" name="sale_id" type="text" value="54F878724V5089715">
            </div>
            <div class="form-group">
                <div class="col-sm-offset-5">
                    <button  type="submit"  class="btn btn-success">Refund Now</button>
                </div>
            </div>
        </form>
    </div>
</div><!-- /.container -->

<?php $this->load->view('theme/footer') ?>
