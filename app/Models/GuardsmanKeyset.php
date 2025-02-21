<?php

namespace App\Models;

class GuardsmanKeyset
{
    public int $id = 0;
    public String $user_id = "";
    public String $name = "";
    public String $key = "";
    public String $scopes = "";

    public function __construct(int $id, String $user_id, String $name, String $key, String $scopes) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->name = $name;
        $this->key = $key;
        $this->scopes = $scopes;
    }

    public function hasScope(String $scope) {
        $scopeList = json_decode($this->scopes);

        return in_array($scope, $scopeList);
    }
}
