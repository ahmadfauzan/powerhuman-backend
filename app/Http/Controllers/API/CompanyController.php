<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function fetch(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $limit = $request->input('limit', 10);

        // powerhuman.com/api/company?id=1
        if ($id) {
            $company = Company::whereHas('users', function ($query) {
                $query->where('user_id', Auth::id());
            })->with(['users'])->find($id);

            if ($company) {
                return ResponseFormatter::success($company, 'Company found');
            }

            return ResponseFormatter::error('Company not found', 404);
        }

        // powerhuman.com/api/company
        $companies = Company::with(['users'])->whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        });

        // powerhuman.com/api/company?name=...
        if ($name) {

            $companies->where('name', 'like', '%' . $name . '%');

            if ($companies->paginate($limit)->items()) {

                return ResponseFormatter::success(
                    $companies->paginate($limit),
                    'Companies Found'
                );
            }

            return ResponseFormatter::error('Company not found', 404);
        }

        return ResponseFormatter::success(
            $companies->paginate($limit),
            'Companies Found'
        );
    }

    public function create(CreateCompanyRequest $request)
    {
        try {
            //  Upload Logo
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/logos');
            }

            //  Create Company
            $company = Company::create([
                'name' => $request->name,
                'logo' => $path,
            ]);

            if (!$company) {
                throw new Exception('Company not created');
            }

            //  Attach company to user
            $user = User::find(Auth::id());
            $user->companies()->attach($company->id);

            //  Load user at company
            $company->load('users');

            return ResponseFormatter::success($company, 'Company created');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateCompanyRequest $request, $id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                throw new Exception('Company not found');
            }

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/logos');
            }

            $company->update([
                'name' => $request->name,
                'logo' => $path
            ]);

            return ResponseFormatter::success($company, 'Company updated');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }
}