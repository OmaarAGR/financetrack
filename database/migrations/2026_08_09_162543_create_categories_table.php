<?php

use Database\Seeders\CategorySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // user_id null => categoría del sistema (semilla), visible para todos.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('icon', 32)->default('tag');
            $table->string('color', 7)->default('#6b7280');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
        });

        // Las categorías del sistema son datos de referencia, no datos de
        // desarrollo: se siembran aquí para que existan en cualquier entorno
        // (incluida producción) apenas se corre `migrate`, sin depender de
        // que alguien recuerde ejecutar `db:seed`.
        (new CategorySeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
