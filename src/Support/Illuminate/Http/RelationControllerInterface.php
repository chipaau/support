<?php

namespace Support\Illuminate\Http;

interface RelationControllerInterface {
    public function index($resourceId);
    public function show($resourceId, $childResourceId);
    public function store($resourceId);
    public function update($resourceId, $childResourceId);
    public function destroy($resourceId, $childResourceId);
    public function relation();
}