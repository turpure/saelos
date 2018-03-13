<?php

namespace App\Http\Controllers;

use App\Notifications\CompanyUpdated;
use Auth;
use App\Company;
use App\Http\Resources\CompanyCollection;
use App\User;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use App\Http\Resources\Company as CompanyResource;

class CompanyController extends Controller
{
    const INDEX_WITH = [
        'user',
        'people',
        'people.company',
        'people.deals',
        'people.deals.people',
        'deals',
        'deals.company',
        'deals.people',
        'deals.customFields',
        'deals.customFields.customField',
        'customFields',
        'customFields.customField',
        'notes',
        'notes.user',
        'documents',
        'documents.user',
    ];

    const SHOW_WITH = [
        'activities',
        'activities.details',
        'user',
        'people',
        'people.company',
        'people.deals',
        'people.deals.people',
        'deals',
        'deals.company',
        'deals.people',
        'deals.customFields',
        'deals.customFields.customField',
        'customFields',
        'customFields.customField',
        'notes',
        'notes.user',
        'documents',
        'documents.user',
    ];

    public function index(Request $request)
    {
        $companies = Company::with(static::INDEX_WITH);

        if ($searchString = $request->get('searchString')) {
            $companies = Company::search($searchString, $companies);
        }

        $companies->orderBy('id', 'desc');

        return new CompanyCollection($companies->paginate());
    }

    public function show($id)
    {
        $with = static::SHOW_WITH;

        unset($with[0]);

        $with['activities'] = function(MorphMany $q) use ($id) {
            $query = $q->getQuery();
            /** @var Builder $builder */
            $builder = $query->getQuery();

            $newQuery = $builder->newQuery();
            $newQuery->from($builder->from);
            $newQuery->whereNested(function(QBuilder $q) use ($builder) {
                $q->mergeWheres($builder->wheres, $builder->bindings);
            });
            $newQuery->whereNested(function($q) use ($id) {
                $q->where('activities.company_id', $id);
            }, 'or');

            $q->setQuery($newQuery);
        };

        $company = Company::with($with)->find($id);

        echo $company->toJson();die;

        return new CompanyResource(Company::with($with)->find($id));
    }

    public function update(Request $request, $id)
    {
        /** @var Company $company */
        $company = Company::findOrFail($id);
        $data = $request->all();
        $customFields = $data['custom_fields'] ?? [];

        $company->user()->associate(Auth::user());
        $company->update($data);
        $company->assignCustomFields($customFields);

        \App\Events\CompanyUpdated::broadcast($company);
        Auth::user()->notify(new CompanyUpdated($company));

        return $this->show($company->id);
    }

    public function store(Request $request)
    {
        $company = Company::create($request->all());

        return $this->update($request, $company->id);
    }

    public function destroy($id)
    {
        Company::findOrFail($id)->delete();

        return '';
    }
}
