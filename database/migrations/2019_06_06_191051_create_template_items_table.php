<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('template_id')->unsigned();
            $table->string('description');
            $table->integer('due_interval')->nullable();
            $table->string('due_unit')->nullable();
            $table->integer('urgency')->nullable();
            $table->integer('assignee_id')->nullable();
            $table->integer('task_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('template_id')
                ->references('id')
                ->on('templates')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_items');
    }
}
