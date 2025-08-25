<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MainAPIController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ARBG\GeneController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\Sars\SarsController;
use App\Http\Controllers\Backend\FileController;
use App\Http\Controllers\ARBG\ARBGHomeController;

use App\Http\Controllers\ARBG\BacteriaController;
use App\Http\Controllers\Empodat\StatisticsController as EmpodatStatisticsController;

use App\Http\Controllers\Ecotox\EcotoxController;
use App\Http\Controllers\Indoor\IndoorController;
use App\Http\Controllers\Sars\SarsHomeController;
use App\Http\Controllers\Backend\GeneralController;
use App\Http\Controllers\Backend\ProjectController;
use App\Http\Controllers\Empodat\EmpodatController;
use App\Http\Controllers\Passive\PassiveController;
use App\Http\Controllers\Backend\QueryLogController;
use App\Http\Controllers\Backend\TemplateController;
use App\Http\Controllers\Susdat\DuplicateController;
use App\Http\Controllers\Susdat\SubstanceController;
use App\Http\Controllers\Bioassay\BioassayController;
use App\Http\Controllers\DatabaseDirectoryController;
use App\Http\Controllers\Ecotox\EcotoxHomeController;
use App\Http\Controllers\Ecotox\EcotoxCREDEvaluationController;
use App\Http\Controllers\Ecotox\EcotoxCREDEvaluationHomeController;
use App\Http\Controllers\Ecotox\LowestPNECController;
use App\Http\Controllers\Indoor\IndoorHomeController;
use App\Http\Controllers\Empodat\EmpodatHomeController;
use App\Http\Controllers\Passive\PassiveHomeController;
use App\Http\Controllers\Empodat\UniqueSearchController;
use App\Http\Controllers\Bioassay\BioassayHomeController;
use App\Http\Controllers\Dashboard\DashboardMainController;
use App\Http\Controllers\SLE\SuspectListExchangeHomeController;
use App\Http\Controllers\SLE\SuspectListExchangeController;
use App\Http\Controllers\Prioritisation\ModellingDanubeController;
use App\Http\Controllers\Prioritisation\ModellingScarceController;
use App\Http\Controllers\Prioritisation\MonitoringDanubeController;
use App\Http\Controllers\Prioritisation\MonitoringScarceController;
use App\Http\Controllers\Prioritisation\PrioritisationHomeController;
use App\Http\Controllers\Empodat\DataCollectionTemplateFileController;
use App\Http\Controllers\ARGB\AntibioticResistanceBacteriaGeneHomeController;

// Route::get('/', function () {
//     return redirect()->route('landing.index');
// });

// Route::get('/landing', [DatabaseDirectoryController::class, 'index'])->name('landing.index');

Route::get('/', [DatabaseDirectoryController::class, 'index'])->name('home');

// Test route to check if global auth is working
Route::get('/test-public', function() {
    return 'This is a public test route';
})->name('test.public');


Route::prefix('backend')->middleware('auth')->group(function () {
    Route::get('overview', [DashboardMainController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::get('/user-data', [UserController::class, 'getUserData'])->middleware('auth');
    
    Route::resource('projects', ProjectController::class);
    
    Route::resource('templates', TemplateController::class);
    Route::get('templates/{template}/download', [TemplateController::class, 'download'])->name('templates.download');
    
    Route::resource('files', FileController::class);
    // Specific templates for a database entity code
    
    
    Route::resource('general_route', GeneralController::class);
    Route::resource('querylog', QueryLogController::class)->middleware('auth');
});

Route::prefix('backend')->group(function () {
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('templates/entity/{code}', [TemplateController::class, 'specificIndex'])->name('templates.specific.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/apiresources', [MainAPIController::class, 'index'])->name('apiresources.index');
    Route::post('/apiresources', [MainAPIController::class, 'store'])->name('apiresources.store');
    Route::delete('/apiresources/destroy', [MainAPIController::class, 'destroy'])->name('apiresources.destroy');
});

// Route::prefix('databases')->middleware('auth')->group(function () {
//     Route::get('/', [DatabaseDirectoryController::class, 'index'])->name('databases.index');
// });

Route::prefix('susdat')->group(function () {
    Route::get('substances/filter', [SubstanceController::class, 'filter'])->name('substances.filter');
    Route::get('substances/search', [SubstanceController::class, 'search'])->name('substances.search');
    Route::get('duplicates/filter/', [DuplicateController::class, 'filter'])->name('duplicates.filter');
    
    Route::get('duplicates/records/{pivot}/{pivot_value}', [DuplicateController::class, 'records'])->name('duplicates.records');
    Route::post('duplicates/records/handle', [DuplicateController::class, 'handleDuplicates'])->middleware('auth')->name('duplicates.handleDuplicates');
    Route::get('duplicates/merge-history', [DuplicateController::class, 'mergeHistory'])->name('duplicates.mergeHistory');
    Route::post('duplicates/{id}/restore', [DuplicateController::class, 'restore'])->middleware('auth')->name('duplicates.restore');
    
    Route::resource('substances', SubstanceController::class)->only(['index', 'show']);
    Route::resource('substances', SubstanceController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::resource('duplicates', DuplicateController::class)->middleware('auth');

    Route::get('substances-audited', [SubstanceController::class, 'withAudits'])->name('substances.audited');
    Route::get('substances/{substance}/audits', [SubstanceController::class, 'audits'])->name('substances.audits');
    
    // Batch Conversion Routes
    Route::get('batch', [App\Http\Controllers\Susdat\BatchConversionController::class, 'index'])->name('susdat.batch.index');
    Route::post('batch/convert', [App\Http\Controllers\Susdat\BatchConversionController::class, 'convert'])->name('susdat.batch.convert');
    Route::get('batch/update', [App\Http\Controllers\Susdat\BatchConversionController::class, 'update'])->name('susdat.batch.update');
    Route::get('batch/download/csv', [App\Http\Controllers\Susdat\BatchConversionController::class, 'downloadCsv'])->name('susdat.batch.download.csv');
});

Route::prefix('empodat')->group(function () {
    Route::get('search/filter/', [EmpodatController::class, 'filter'])->name('codsearch.filter');
    Route::get('search/search/', [EmpodatController::class, 'search'])->name('codsearch.search');
    Route::get('search/downloadjob/{query_log_id}', [EmpodatController::class, 'startDownloadJob'])->name('codsearch.download');
    Route::get('search/download/{filename}', [EmpodatController::class, 'downloadCsv'])
    ->name('csv.download');
    Route::resource('search', EmpodatController::class)->names([
        'index'   => 'codsearch.index',
        'create'  => 'codsearch.create',
        'store'   => 'codsearch.store',
        'show'    => 'codsearch.show',
        'edit'    => 'codsearch.edit',
        'update'  => 'codsearch.update',
        'destroy' => 'codsearch.destroy',
    ]);
    
    // Public routes
    Route::resource('home', EmpodatHomeController::class)->only(['index'])->names([
        'index' => 'codhome.index',
    ]);
    
    // Authenticated routes
    Route::resource('home', EmpodatHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy'])->names([
        'create' => 'codhome.create',
        'store' => 'codhome.store',
        'edit' => 'codhome.edit',
        'update' => 'codhome.update',
        'destroy' => 'codhome.destroy',
    ]);
    Route::get('dctitems/dctupload/{dctitem_id}', [DataCollectionTemplateFileController::class, 'uploadNewTemplate'])->name('dctitems.upload_new_template');
    Route::post('dctitems/dctstore/{dctitem_id}', [DataCollectionTemplateFileController::class, 'storeNewTemplate'])->name('dctitems.store_new_template');
    Route::get('dctitems/dctdownload/{id}', [DataCollectionTemplateFileController::class, 'downloadTemplate'])->name('dctitems.donwload_template');
    Route::delete('dctitems/destroyfiles/{dctitem_id}', [DataCollectionTemplateFileController::class, 'destroyFile'])->name('dctitems.delete_template');
    Route::get('dctitems/files/{id}', [DataCollectionTemplateFileController::class, 'indexFiles'])->name('dctitems.index_files');
    Route::resource('dctitems', DataCollectionTemplateFileController::class)->only(['index']);
    Route::resource('dctitems', DataCollectionTemplateFileController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    
    // generate unique search tables
    Route::post('unique/search/country', [UniqueSearchController::class, 'countries'])->name('cod.unique.search.countries');
    Route::post('unique/search/matrix', [UniqueSearchController::class, 'matrices'])->name('cod.unique.search.matrices');
    
    
    Route::post('unique/search/dbentity', [UniqueSearchController::class, 'updateDatabaseEntitiesCounts'])->name('update.dbentities.counts');
    
    Route::get('templates/entity/{code}', [EmpodatHomeController::class, 'specificIndex'])->name('empodat.templates');

    Route::prefix('statistics')->group(function () {
        // Main statistics overview
        Route::get('/', [EmpodatStatisticsController::class, 'index'])->name('empodat.statistics.index');
        
        // Country Year Statistics
        Route::get('country-year', [EmpodatStatisticsController::class, 'viewCountryStats'])->name('empodat.statistics.countryYear');
        Route::post('generate-country', [EmpodatStatisticsController::class, 'generateCountryStats'])->name('empodat.statistics.generateCountry');
        
        // Matrix Statistics
        Route::get('matrix', [EmpodatStatisticsController::class, 'matrix'])->name('empodat.statistics.matrix');
        Route::post('generate-matrix', [EmpodatStatisticsController::class, 'generateMatrixStats'])->name('empodat.statistics.generateMatrix');
        
        // Substance Statistics
        Route::get('substance', [EmpodatStatisticsController::class, 'substance'])->name('empodat.statistics.substance');
        Route::post('generate-substance', [EmpodatStatisticsController::class, 'generateSubstanceStats'])->name('empodat.statistics.generateSubstance');
        
        // Country Statistics (simple, without years)
        Route::get('country', [EmpodatStatisticsController::class, 'country'])->name('empodat.statistics.country');
        Route::post('generate-country-simple', [EmpodatStatisticsController::class, 'generateCountrySimpleStats'])->name('empodat.statistics.generateCountrySimple');
        
        // QA/QC Statistics (placeholder for future)
        Route::get('quality', [EmpodatStatisticsController::class, 'quality'])->name('empodat.statistics.quality');
        Route::post('generate-quality', [EmpodatStatisticsController::class, 'generateQualityStats'])->name('empodat.statistics.generateQuality');
        
        // CSV Downloads
        Route::get('download', [EmpodatStatisticsController::class, 'downloadCsv'])->name('empodat.statistics.download');

    });
    
});

Route::prefix('ecotox')->group(function () {
    Route::resource('ecotoxhome', EcotoxHomeController::class)->only(['index']);;
    Route::resource('ecotoxhome', EcotoxHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    Route::prefix('lowestpnec')->group(function () {
        Route::get('index', [LowestPNECController::class, 'index'])->name('ecotox.lowestpnec.index');
        Route::get('show/{sus_id}', [LowestPNECController::class, 'show'])->name('ecotox.lowestpnec.show');
        Route::get('countAll', [LowestPNECController::class, 'countAll'])->middleware('auth')->name('ecotox.lowestpnec.countAll');
    });
    
    Route::prefix('data')->group(function () {
        Route::get('search/filter/', [EcotoxController::class, 'filter'])->name('ecotox.data.search.filter');
        Route::get('search/search/', [EcotoxController::class, 'search'])->name('ecotox.data.search.search');
        Route::get('show/{id}', [EcotoxController::class, 'show'])->name('ecotox.data.show');
    });


    Route::get('e/countAll', [EcotoxController::class, 'countAll'])->middleware('auth')->name('ecotox.ecotox.countAll');
    Route::get('ee/countAll', [EcotoxHomeController::class, 'countAll'])->middleware('auth')->name('ecotox.countAll');
    Route::get('unique/search/substances', [EcotoxHomeController::class, 'syncNewSubstances'])->name('ecotox.unique.search.substances');
    
    // CRED Evaluation routes
    Route::prefix('credevaluation')->group(function () {
        Route::resource('home', EcotoxCREDEvaluationHomeController::class)->only(['index'])->names(['index' => 'ecotox.credevaluation.home.index']);
        Route::get('search/filter/', [EcotoxCREDEvaluationController::class, 'filter'])->name('ecotox.credevaluation.search.filter');
        Route::get('search/search/', [EcotoxCREDEvaluationController::class, 'search'])->name('ecotox.credevaluation.search.search');
        Route::get('countAll', [EcotoxCREDEvaluationController::class, 'countAll'])->middleware('auth')->name('ecotox.credevaluation.countAll');
        
        // CRED Evaluation Form Routes
        Route::get('form/{recordId}', [EcotoxCREDEvaluationController::class, 'showForm'])->name('ecotox.credevaluation.form');
        Route::get('demo', [EcotoxCREDEvaluationController::class, 'showDemoForm'])->name('ecotox.credevaluation.demo');
        
        // CRED Evaluation Modal Routes
        Route::get('data/{recordId}', [EcotoxCREDEvaluationController::class, 'getModalData'])->name('ecotox.credevaluation.modal.data');
        Route::get('history/{recordId}', [EcotoxCREDEvaluationController::class, 'getEvaluationHistory'])->name('ecotox.credevaluation.modal.history');
        Route::post('save', [EcotoxCREDEvaluationController::class, 'saveEvaluation'])->name('ecotox.credevaluation.modal.save');
    });
    
});

Route::prefix('sle')->group(function () {
    Route::get('slehome', [SuspectListExchangeHomeController::class, 'index'])->name('slehome.index');
    // Route::resource('slehome', SuspectListExchangeHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    Route::get('slehome/countAll', [SuspectListExchangeHomeController::class, 'countAll'])->middleware('auth')->name('slehome.countAll');
    
    // CRUD routes for SuspectListExchangeSource
    Route::get('sources', [SuspectListExchangeController::class, 'main'])->name('sle.sources.index')->withoutMiddleware(['auth', 'role:admin|super_admin|sle']);
    Route::get('sources/database', [SuspectListExchangeController::class, 'index'])->name('sle.sources.database')->middleware('auth');
    Route::get('sources/refresh', [SuspectListExchangeController::class, 'refresh'])->name('sle.sources.refresh')->middleware('auth');
    Route::resource('sources', SuspectListExchangeController::class)->middleware('auth')->except(['index'])->names([
        'create'  => 'sle.sources.create',
        'store'   => 'sle.sources.store',
        'show'    => 'sle.sources.show',
        'edit'    => 'sle.sources.edit',
        'update'  => 'sle.sources.update',
        'destroy' => 'sle.sources.destroy',
    ]);
});

Route::prefix('arbg')->group(function () {
    Route::resource('arbghome', ARBGHomeController::class)->only(['index']);
    Route::resource('arbghome', ARBGHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::get('countAll', [ARBGHomeController::class, 'countAll'])->middleware('auth')->name('arbg.countAll');
    Route::get('bacteria/countAll', [ARBGHomeController::class, 'countAllBacteria'])->middleware('auth')->name('arbg.bacteria.countAll');
    
    
    
    Route::prefix('bacteria')->group(function () {
        Route::get('search/filter/', [BacteriaController::class, 'filter'])->name('arbg.bacteria.search.filter');
        Route::get('search/search/', [BacteriaController::class, 'search'])->name('arbg.bacteria.search.search');
        Route::get('countAll', [ARBGHomeController::class, 'countAllBacteria'])->middleware('auth')->name('arbg.bacteria.countAll');
    });
    
    Route::prefix('gene')->group(function () {
        Route::get('search/filter/', [GeneController::class, 'filter'])->name('arbg.gene.search.filter');
        Route::get('search/search/', [GeneController::class, 'search'])->name('arbg.gene.search.search');
        Route::get('countAll', [ARBGHomeController::class, 'countAllGene'])->middleware('auth')->name('arbg.gene.countAll');
    });
    
});

Route::prefix('indoor')->group(function () {
    Route::resource('indoorhome', IndoorHomeController::class)->only(['index']);
    Route::resource('indoorhome', IndoorHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    Route::get('search/filter/', [IndoorController::class, 'filter'])->name('indoor.search.filter');
    Route::get('search/search/', [IndoorController::class, 'search'])->name('indoor.search.search');
    
    Route::get('indoor/countAll', [IndoorHomeController::class, 'countAll'])->middleware('auth')->name('indoor.countAll');
});

Route::prefix('passive')->group(function () {
    Route::resource('passivehome', PassiveHomeController::class)->only(['index']);
    Route::resource('passivehome', PassiveHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    Route::get('search/filter/', [PassiveController::class, 'filter'])->name('passive.search.filter');
    Route::get('search/search/', [PassiveController::class, 'search'])->name('passive.search.search');
    
    Route::get('passive/countAll', [PassiveHomeController::class, 'countAll'])->middleware('auth')->name('passive.countAll');
    
});

Route::prefix('bioassays')->group(function () {
    Route::resource('bioassayhome', BioassayHomeController::class)->only(['index']);
    Route::resource('bioassayhome', BioassayHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    Route::get('search/filter/', [BioassayController::class, 'filter'])->name('bioassay.search.filter');
    Route::get('search/search/', [BioassayController::class, 'search'])->name('bioassay.search.search');
    
    Route::resource('search', BioassayController::class)->names([
        'index'   => 'bioassay.search.index',
        'create'  => 'bioassay.search.create',
        'store'   => 'bioassay.search.store',
        'show'    => 'bioassay.search.show',
        'edit'    => 'bioassay.search.edit',
        'update'  => 'bioassay.search.update',
        'destroy' => 'bioassay.search.destroy',
    ]);
    
    Route::get('bioassay/countAll', [BioassayHomeController::class, 'countAll'])->middleware('auth')->name('bioassay.countAll');
});

Route::prefix('sars')->group(function () {
    
    Route::get('search/filter/', [SarsController::class, 'filter'])->name('sars.search.filter');
    Route::get('search/search/', [SarsController::class, 'search'])->name('sars.search.search');
    Route::get('search/downloadjob/{query_log_id}', [SarsController::class, 'startDownloadJob'])->name('sars.search.download');
    Route::get('search/download/{filename}', [SarsController::class, 'downloadCsv'])
    ->name('sars.csv.download');
    Route::resource('search', SarsController::class)->names([
        'index'   => 'sars.search.index',
        'create'  => 'sars.search.create',
        'store'   => 'sars.search.store',
        'show'    => 'sars.search.show',
        'edit'    => 'sars.search.edit',
        'update'  => 'sars.search.update',
        'destroy' => 'sars.search.destroy',
    ]);
    
    // Public routes
    Route::resource('home', SarsHomeController::class)->only(['index'])->names([
        'index' => 'sars.home.index',
    ]);
    
    // Authenticated routes
    Route::resource('home', SarsHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy'])->names([
        'create' => 'sars.home.create',
        'store' => 'sars.home.store',
        'edit' => 'sars.home.edit',
        'update' => 'sars.home.update',
        'destroy' => 'sars.home.destroy',
    ]);
    
    
});


Route::prefix('prioritisation')->group(function () {
    Route::resource('prioritisationhome', PrioritisationHomeController::class)->only(['index']);
    Route::resource('prioritisationhome', PrioritisationHomeController::class)->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    Route::get('/monitoring-scarce', [MonitoringScarceController::class, 'index'])->name('prioritisation.monitoring-scarce.index');
    Route::get('/monitoring-danube', [MonitoringDanubeController::class, 'index'])->name('prioritisation.monitoring-danube.index');
    
    Route::get('/modelling-scarce', [ModellingScarceController::class, 'index'])->name('prioritisation.modelling-scarce.index');
    Route::get('/modelling-danube', [ModellingDanubeController::class, 'index'])->name('prioritisation.modelling-danube.index');
    
    
    Route::get('/monitoring-scarce/filter', [MonitoringScarceController::class, 'filter'])->name('prioritisation.monitoring-scarce.filter');
    
    Route::get('prioritisation/countAll', [PrioritisationHomeController::class, 'countAll'])->middleware('auth')->name('prioritisation.countAll');
});




Route::get('/send-test-email', [EmailTestController::class, 'sendTestEmail'])->middleware('auth');

require __DIR__.'/auth.php';
