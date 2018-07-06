<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AuthApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function doctor(Request $request)
    {
        dd(1);

        $this->validate($request, [
            "username" => "unique:users,username",
            "email" => "required",
            "displayname" => "required",
            "password" => "min:4",
            "group_id" => "required",
            "active" => "required",
        ]);
        $doctor = DB::table('users');
        $doctor->username = $request->input('username');
        $doctor->email = $request->input('email');
        $doctor->displayname = $request->input('displayname');
        $doctor->password = bcrypt($request->input['password']);
        $doctor->group_id = $request->input('group_id');
        $doctor->active = $request->input('active');
        $doctor->save();
        return response()->json(array('response' => 'success'));
    }
    public function patient(Request $request)
    {
        $this->validate($request, [
            "name" => "required",
            "description" => "required",
            "size" => "required",
            "image" => "required",
            "category_id" => "required",
            "price" => "required",
        ]);
        $product = new Product();
        $product->name = $request->input('name');
        $product->description = $request->input('description');
        $product->size = $request->input('size');
        $product->image = $request->input('image');
        $product->category_id = $request->input('category_id');
        $product->price = $request->input('price');
        $product->save();
        return response()->json(array('response' => 'success'));
    }

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
