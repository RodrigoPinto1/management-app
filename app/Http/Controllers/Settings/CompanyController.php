<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function edit()
    {
        $company = Company::first();

        return Inertia::render('settings/Company', [
            'company' => $company,
        ]);
    }

    public function update(Request $request)
    {
        $company = Company::first();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1024'],
            'postal_code' => ['nullable', 'string', 'max:64'],
            'locality' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:64'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company_logos', 'public');
            if ($company && $company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $path;
        }

        if (! $company) {
            $company = Company::create($data);
            activity('configurações')
                ->causedBy(auth()->user())
                ->performedOn($company)
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Criou os dados da empresa');
        } else {
            $company->update($data);
            activity('configurações')
                ->causedBy(auth()->user())
                ->performedOn($company)
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Atualizou os dados da empresa');
        }

        return to_route('company.edit');
    }
}
