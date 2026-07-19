<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
class PlatformForm extends Form
{
    public function rules()
    {
        return [
            'nickname' => ['required' , 'string' , 'max:50'],
            'platform' => ['required' , 'string' , 'in:pc,xbox,ps,mobile' ,
                Rule::unique('platforms', 'platform')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                })
            ]
        ];
    }

    public $nickname = '';

    public $platform = '';

    public function store()
    {
        $this->validate();

        auth()->user()->platforms()->create([
            'nickname' => $this->nickname,
            'platform' => $this->platform,
        ]);

        $this->reset();
    }
}
