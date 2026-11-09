<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** The logo of each existing insurer, by org_code (InsuranceCompanySeeder holds the same paths for fresh installs). */
    private const LOGOS = [
        2 => 'Iran.webp',
        4 => 'Asia.webp',
        5 => 'Dana.webp',
        6 => 'Moallem.webp',
        7 => 'Alborz.svg',
        8 => 'Karafarin.webp',
        9 => 'Parsian.webp',
        10 => 'Tosee.webp',
        11 => 'Sina.webp',
        12 => 'Saman.webp',
        13 => 'Razi.webp',
        14 => 'Novin.webp',
        15 => 'Day.webp',
        16 => 'Mihan.webp',
        17 => 'Mellat.webp',
        18 => 'Hafez.webp',
        19 => 'Amin.png',
        20 => 'IranMoein.png',
        21 => 'Omid.png',
        23 => 'Pasargad.webp',
        25 => 'Iranian.webp',
        26 => 'Kosar.svg',
        32 => 'Taavon.webp',
        33 => 'Ma.webp',
        34 => 'Asmari.webp',
        35 => 'Sarmad.svg',
        36 => 'Arman.webp',
        39 => 'TejaratNo.svg',
        42 => 'Dana.webp',
        43 => 'Central.webp',
        45 => 'Hekmat.svg',
        46 => 'Khavarmiyaneh.svg',
        47 => 'Baran.png',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->string('logo', 255)->nullable()->after('website');
        });

        foreach (self::LOGOS as $orgCode => $file) {
            DB::table('insurance_companies')
                ->where('org_code', $orgCode)
                ->update(['logo' => "images/insurance/logo/{$file}"]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
