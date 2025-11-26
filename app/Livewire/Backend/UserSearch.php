<?php

declare(strict_types=1);

namespace App\Livewire\Backend;

use App\Models\User;
use Livewire\Component;

class UserSearch extends Component
{
    public string $search = '';

    public ?int $selectedUserId = null;

    public ?array $selectedUser = null;

    public string $fieldName = 'uploaded_by';

    public function mount(?int $selectedUserId = null, string $fieldName = 'uploaded_by'): void
    {
        $this->fieldName = $fieldName;

        if ($selectedUserId) {
            $this->selectedUserId = $selectedUserId;
            $user = User::find($selectedUserId);
            if ($user) {
                $this->selectedUser = [
                    'id' => $user->id,
                    'name' => $user->last_name.', '.$user->first_name,
                    'email' => $user->email,
                ];
            }
        }
    }

    public function render()
    {
        $results = collect();

        if (strlen($this->search) >= 2) {
            $results = User::where(function ($query) {
                $query->where('last_name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('first_name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('email', 'ilike', '%'.$this->search.'%');
            })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(20)
                ->get();
        }

        return view('livewire.backend.user-search', [
            'results' => $results,
        ]);
    }

    public function selectUser(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $this->selectedUserId = $user->id;
            $this->selectedUser = [
                'id' => $user->id,
                'name' => $user->last_name.', '.$user->first_name,
                'email' => $user->email,
            ];
            $this->search = '';
        }
    }

    public function clearUser(): void
    {
        $this->selectedUserId = null;
        $this->selectedUser = null;
        $this->search = '';
    }
}
