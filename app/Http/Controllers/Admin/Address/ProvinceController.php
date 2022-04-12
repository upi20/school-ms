<?php

namespace App\Http\Controllers\Admin\Address;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address\Province;
use Illuminate\Support\Facades\DB;
use Yajra\Datatables\Datatables;
use League\Config\Exception\ValidationException;


class ProvinceController extends Controller
{
    private $query = [];
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $this->query['regencie'] = <<<SQL
                (select count(*) from address_regencies where province_id = address_provinces.id)
            SQL;
            $this->query['regencie_alias'] = 'regencie';

            $this->query['district'] = <<<SQL
                (select count(*) from address_districts
                    join address_regencies
                    on address_districts.regency_id = address_regencies.id
                    where address_regencies.province_id = address_provinces.id)
            SQL;
            $this->query['district_alias'] = 'district';

            $this->query['village'] = <<<SQL
                (select count(*) from address_villages
                    join address_districts
                    on address_villages.district_id = address_districts.id
                    join address_regencies
                    on address_districts.regency_id = address_regencies.id
                    where address_regencies.province_id = address_provinces.id)
            SQL;
            $this->query['village_alias'] = 'village';

            $model = Province::select([
                'id', 'name',
                DB::raw("{$this->query['regencie']} as {$this->query['regencie_alias']}"),
                DB::raw("{$this->query['district']} as {$this->query['district_alias']}"),
                DB::raw("{$this->query['village']} as {$this->query['village_alias']}"),
            ]);
            return Datatables::of($model)
                ->addIndexColumn()
                ->filterColumn($this->query['regencie_alias'], function ($query, $keyword) {
                    $query->whereRaw("{$this->query['regencie']} like '%$keyword%'");
                })
                ->filterColumn($this->query['district_alias'], function ($query, $keyword) {
                    $query->whereRaw("{$this->query['district']} like '%$keyword%'");
                })
                ->filterColumn($this->query['village_alias'], function ($query, $keyword) {
                    $query->whereRaw("{$this->query['village']} like '%$keyword%'");
                })
                ->make(true);
        }

        $page_attr = [
            'title' => 'Manage Address Provinces',
            'breadcrumbs' => [
                ['name' => 'Address'],
            ]
        ];
        return view('admin.address.province', compact('page_attr'));
    }

    public function store(Request $request)
    {

        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'id' => ['required', 'int', 'max:99', 'unique:address_provinces'],
            ]);

            Province::create([
                'name' => $request->name,
                'id' => $request->id,
            ]);
            return response()->json();
        } catch (ValidationException $error) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $address = Province::find($request->id);
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $address->name = $request->name;
            $address->save();
            return response()->json();
        } catch (ValidationException $error) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 500);
        }
    }

    public function delete(Request $address)
    {
        try {
            $address = Province::find($address->id);
            $address->delete();
            return response()->json();
        } catch (ValidationException $error) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 500);
        }
    }
}
