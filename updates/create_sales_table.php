<?php namespace Txbutton\App\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateSalesTable extends Migration
{
    public function up()
    {
        Schema::create('txbutton_app_sales', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->integer('wallet_id')->unsigned()->nullable()->index();
            $table->string('hash', 40)->nullable()->index();
            $table->integer('sale_index');
            $table->integer('address_index');
            $table->string('status_name')->nullable();
            $table->string('coin_address')->nullable();
            $table->decimal('coin_balance', 15, 8)->default(0);
            $table->decimal('coin_confirmed', 15, 8)->default(0);
            $table->decimal('coin_price', 15, 8)->default(0);
            $table->decimal('fiat_price', 15, 2)->default(0);
            $table->decimal('exchange_rate', 15, 8)->nullable();
            $table->string('coin_currency', 3)->nullable();
            $table->string('fiat_currency', 3)->nullable();
            $table->boolean('is_paid')->nullable()->index();
            // $table->boolean('is_confirmed')->nullable()->index();
            $table->boolean('is_permanent')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('txbutton_app_sales');
    }
}
