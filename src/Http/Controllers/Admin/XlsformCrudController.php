<?php


namespace Stats4sd\KoboLink\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Illuminate\Support\Facades\Storage;
use Stats4sd\KoboLink\Models\Xlsform;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class XlsformCrudController
 * @package \Stats4SD\KoboLink\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class XlsformCrudController extends CrudController
{
    use ListOperation, CreateOperation, UpdateOperation, DeleteOperation, ShowOperation;

    public function setup()
    {
        CRUD::setModel(Xlsform::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/xlsform');
        CRUD::setEntityNameStrings('xlsform', 'xlsforms');
    }
    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('title');
        CRUD::column('xlsfile')->type('upload')->wrapper([
            'href' => function ($crud, $column, $entry) {
                if ($entry->xlsfile) {
                    return Storage::disk('xlsforms')->url($entry->xlsfile);
                }
                return '#';
            }
        ]);
        CRUD::column('media')->type('upload_multiple');
        CRUD::column('csv_lookups')->type('table')->columns([
            'mysql_name' => 'MySQL Table/View',
            'csv_name' => 'CSV File Name',
        ]);
        CRUD::column('kobo_id')->label('Kobo Form ID')->wrapper([
            'href' => function ($crud, $column, $entry) {
                if ($entry->kobo_id) {
                    return 'https://kf.kobotoolbox.org/#/forms/'.$entry->kobo_id;
                }
                return '#';
            },
        ]);
        CRUD::column('is_active')->type('boolean')->label('Form active on Kobo?');
        CRUD::column('available')->type('boolean')->label('Is the form available for live use?');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(XlsformRequest::class);

        CRUD::field('title');
        CRUD::field('xlsfile')->type('upload')->upload(true);
        CRUD::field('description')->type('textarea');
        CRUD::field('media')->type('upload_multiple')->label('Add any static files that should be pushed to KoboToolBox as media attachments for this form')->upload(true);
        CRUD::field('csv_lookups')->type('table')->columns([
            'mysql_name' => 'MySQL Table Name',
            'csv_name' => 'CSV File Name',
        ])->label('<h4>Add Lookups from the Database</h4>
        <br/><div class="bd-callout bd-callout-info font-weight-normal">
        You should add the name of the MySQL Table or View, and the required name of the resulting CSV file. Every time you deploy this form, the platform will create a new version of the csv file using the data from the MySQL table or view you specify. This file will be uploaded to KoboToolBox as a form media attachment.
        <br/><br/>
        For example, if the form requires a csv lookup file called "households.csv", and the data is available in a view called "households_csv", then you should an entry like this:
            <ul>
                <li>MySQL Table Name = housholds_csv</li>
                <li>CSV File Name = households</li>
            </ul>
        </div>')->entity_singular('CSV Lookup reference');
        CRUD::field('available')->label('If this form should be available to all users as a "live" data collection form, tick this box')->type('checkbox');
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function setupShowOperation()
    {
        $this->crud->set('show.setFromDb', false);

        $this->setupListOperation();

        Crud::button('deploy')
        ->stack('line')
        ->view('crud::buttons.deploy');

        Crud::button('sync')
        ->stack('line')
        ->view('crud::buttons.sync');

        Crud::button('archive')
        ->stack('line')
        ->view('crud::buttons.archive');

        $form = $this->crud->getCurrentEntry();

        Widget::add([
            'type' => 'view',
            'view' => 'crud::widgets.xlsform_kobo_info',
            'form' => $form,
        ])->to('after_content');
    }

    public function deployToKobo(Xlsform $xlsform)
    {
        DeployFormToKobo::dispatch(backpack_auth()->user(), $xlsform);

        return response()->json([
            'title' => $xlsform->title,
            'user' => backpack_auth()->user()->email,
        ]);
    }

    public function syncData(Xlsform $xlsform)
    {
        GetDataFromKobo::dispatchNow(backpack_auth()->user(), $xlsform);

        $submissions = $xlsform->submissions;

        return $submissions->toJson();
    }

    public function downloadSubmissions(Xlsform $xlsform)
    {
        return 'TODO';
    }


    public function archiveOnKobo(Xlsform $xlsform)
    {
        ArchiveKoboForm::dispatch(backpack_auth()->user(), $xlsform);

        return response()->json([
            'title' => $xlsform->title,
            'user' => backpack_auth()->user()->email,
        ]);
    }
}