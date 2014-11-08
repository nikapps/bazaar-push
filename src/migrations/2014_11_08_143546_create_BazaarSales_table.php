<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBazaarSalesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
        Schema::create('bazaar_sale', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('account');
            $table->string('email');
            $table->string('package');
            $table->timestamp('date'); // the exact date for sale item
            $table->integer('price');
            $table->string('token')->index();
            $table->timestamps(); // fetch date
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
        Schema::drop('bazaar_sale');
	}

}
