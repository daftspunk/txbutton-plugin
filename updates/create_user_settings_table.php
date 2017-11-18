<?php namespace Txbutton\App\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateUserSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('txbutton_app_user_settings', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('currency_code', 3)->nullable();
            $table->integer('require_confirm')->default(0);
            $table->string('pos_username')->nullable();
            $table->string('pos_pin')->nullable();
            $table->string('pos_hash', 40)->nullable()->index();
            $table->integer('total_sales')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('txbutton_app_user_settings');
    }
}
