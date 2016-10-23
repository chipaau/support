<?php

namespace Support\Illuminate\Http;

interface ControllerInterface {

    public function index();
    public function show($resourceId);
    public function store();
    public function update($resourceId);
    public function destroy($resourceId);
}