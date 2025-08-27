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
        Schema::create('susdat_usepa', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sus_id');
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->onUpdate('cascade')->onDelete('restrict');
            $table->string('dtsxid', 20);
            $table->string('usepa_formula', 255)->nullable();
            $table->mediumText('usepa_wikipedia')->nullable()->comment('Chemistry US EPA DashBoard >> Details >>  Wikipedia');
            $table->string('usepa_wikipedia_url', 255)->nullable()->comment('Chemistry US EPA DashBoard >> Details >>  Wikipedia');
            $table->float('usepa_Log_Kow_experimental')->nullable()->comment('Chemistry US EPA DashBoard >> Properties >>  LogP: Octanol-Water  >> Experimental >> Average');
            $table->float('usepa_Log_Kow_predicted')->nullable()->comment('Chemistry US EPA DashBoard >> Properties >>  LogP: Octanol-Water  >> Predicted >> Average');
            $table->float('usepa_solubility_experimental')->nullable()->comment('Chemistry US EPA DashBoard >> Properties >>  Water solubility >> Experimental >> Average');
            $table->float('usepa_solubility_predicted')->nullable()->comment('Chemistry US EPA DashBoard >> Properties >>  Water solubility >> Predicted >> Average');
            $table->float('usepa_Koc_min_experimental')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Min value');
            $table->float('usepa_Koc_max_experimental')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Max value');
            $table->float('usepa_Koc_min_predicted')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >>Predicted >> Range >> Min value');
            $table->float('usepa_Koc_max_predicted')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Predicted >> Range >> Max value');
            $table->float('usepa_Life_experimental')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Experimental Average');
            $table->float('usepa_Life_predicted')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Predicted Average');
            $table->float('usepa_BCF_experimental')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Experimental Average');
            $table->float('usepa_BCF_predicted')->nullable()->comment('Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Predicted Average');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('susdat_usepa');
    }
};
