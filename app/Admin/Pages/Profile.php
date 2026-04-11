<?php

namespace App\Admin\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('current_password')
                    ->label('当前密码')
                    ->helperText('如需修改密码，请先输入当前密码')
                    ->password()
                    ->maxLength(255),

                TextInput::make('new_password')
                    ->label('新密码')
                    ->password()
                    ->maxLength(255)
                    ->minLength(8),

                TextInput::make('new_password_confirmation')
                    ->label('确认新密码')
                    ->password()
                    ->maxLength(255)
                    ->same('new_password'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // 验证当前密码
        if (! empty($data['new_password'])) {
            if (empty($data['current_password'])) {
                $this->addError('data.current_password', '修改密码需要提供当前密码');

                return;
            }

            if (! Hash::check($data['current_password'], $user->password)) {
                $this->addError('data.current_password', '当前密码不正确');

                return;
            }
        }

        // 检查邮箱是否已被其他用户使用
        if ($data['email'] !== $user->email) {
            $existingUser = User::where('email', $data['email'])
                ->where('id', '!=', $user->id)
                ->first();
            if ($existingUser) {
                $this->addError('data.email', '该邮箱已被其他用户使用');

                return;
            }
        }

        // 更新基本信息
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        // 如果输入了新密码，更新密码
        if (! empty($data['new_password'])) {
            $updateData['password'] = Hash::make($data['new_password']);
        }

        User::where('id', $user->id)->update($updateData);

        // 重置密码字段
        $this->form->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'current_password' => '',
            'new_password' => '',
            'new_password_confirmation' => '',
        ]);

        $this->success('个人信息已更新');
    }
}
