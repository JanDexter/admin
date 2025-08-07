<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'name')) {
                $table->renameColumn('name', 'company_name');
            }
            if (Schema::hasColumn('customers', 'company')) {
                $table->dropColumn('company');
            }
            if (!Schema::hasColumn('customers', 'contact_person')) {
                $table->string('contact_person')->after('company_name');
            }
            if (!Schema::hasColumn('customers', 'website')) {
                $table->string('website')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }

            // Change status to allow more values
            $table->string('status')->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'company_name')) {
                $table->renameColumn('company_name', 'name');
            }
            if (!Schema::hasColumn('customers', 'company')) {
                $table->string('company')->nullable();
            }
            if (Schema::hasColumn('customers', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
            if (Schema::hasColumn('customers', 'website')) {
                $table->dropColumn('website');
            }
            if (Schema::hasColumn('customers', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('customers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });
    }
};
