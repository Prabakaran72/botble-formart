<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
    public function up()
    {
        Schema::create('ec_product_recurring_option', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('product_id');
            $table->date('entry_date');
            $table->enum('unsubscribe',['Yes','No'])->default('No');//if needed
            $table->enum('recurring_daily',['Yes','No'])->nullable();
            $table->enum('recurring_weekly',['Yes','No'])->nullable();
            $table->string('days')->nullable()->comment('sun,mon.thu,wed,thr,fri,sat');
            $table->enum('recurring_monthly',['Yes','No']);
            $table->string('dates')->comment('1,5,14,17,25');
            // $table->enum('recurring_yearly',['Yes','No']);
            // $table->string('dates');//2023-01-01,2020-04-15,2020-08-14,2020-12-31
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->integer('update_by')->nullable();
            $table->string('update_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *ss
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ec_product_recurring_option');
    }
};
