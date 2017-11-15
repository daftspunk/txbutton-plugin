<?php namespace Txbutton\App\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateWalletsTable extends Migration
{
    public function up()
    {
        Schema::create('txbutton_app_wallets', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('xpub')->nullable();
            $table->string('hash', 40)->nullable()->index();
            $table->integer('last_address_index')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('txbutton_app_wallets');
    }
}
