<?php $this->load->view('theme/header') ?>
        <div class="container">

            <div class="starter-template">
                <h1>PayPal Payment</h1>
                <p class="lead">Pay Now</p>
            </div>

            <div class="contact-form">

                <p class="notice error"><?= $this->session->flashdata('error_msg') ?></p><br/>
                <p class="notice error"><?= $this->session->flashdata('success_msg') ?></p><br/>

                <form method="post" class="form-horizontal" role="form" action="<?= base_url() ?>paypal/create_payment_with_paypal">
                    <fieldset>
                        <div class="form-group">
                            <label class="label label-primary">item_name</label>
                            <input class="form-control" title="item_name" name="item_name" type="text" value="Syed Aijaz Hussain Naqvi">
                        </div>
                        <div class="form-group">
                            <label class="label label-primary">item_number</label>
                            <input class="form-control" title="item_number" name="item_number" type="text" value="12345">
                        </div>
                        <div class="form-group">
                            <label class="label label-primary">item_description</label>
                            <input class="form-control" title="item_description" name="item_description" type="text" value="Test Item Description">
                        </div>
                        <div class="form-group">
                            <label class="label label-primary">item_tax</label>
                            <input class="form-control" title="item_tax" name="item_tax" type="text" value="1">
                        </div>
                        <div class="form-group">
                            <label class="label label-primary">item_price</label>
                            <input class="form-control" title="item_price" name="item_price" type="text" value="7">
                        </div>
                        <div class="form-group">
                            <label class="label label-primary">details_tax</label>
                            <input class="form-control" title="details_tax" name="details_tax" type="text" value="7">
                        </div>
                        <div class="form-group">
                            <label class="label label-primary">details_subtotal</label>
                            <input class="form-control" title="details_subtotal" name="details_subtotal" type="text" value="7">
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-5">
                                <button  type="submit"  class="btn btn-success">Pay Now</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div><!-- /.container -->

<?php $this->load->view('theme/footer') ?>
