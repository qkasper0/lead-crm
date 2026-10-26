<?php

namespace app\commands;

use app\components\LeadIntake;
use app\models\Lead;
use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Начальные данные.
 *
 *   php yii seed/admin admin@example.com 'пароль' 'Имя'   — создать администратора
 *   php yii seed/demo                                     — демо-менеджеры и лиды
 *   php yii seed/token user@example.com                   — выпустить API-токен
 */
class SeedController extends Controller
{
    public function actionAdmin(string $email, string $password, string $name = 'Администратор'): int
    {
        if (User::find()->where(['email' => mb_strtolower($email)])->exists()) {
            $this->stderr("Пользователь $email уже существует.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }
        $user = new User(['name' => $name, 'email' => mb_strtolower($email), 'role' => User::ROLE_ADMIN, 'is_active' => true]);
        $user->setPassword($password);
        $user->save(false);
        $this->stdout("Администратор $email создан.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /** php yii seed/token manager@example.com — выпустить API-токен пользователю (старый перестанет работать). */
    public function actionToken(string $email): int
    {
        $user = User::findOne(['email' => mb_strtolower($email)]);
        if (!$user) {
            $this->stderr("Пользователь $email не найден.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        $this->stdout($user->generateApiToken() . "\n");
        return ExitCode::OK;
    }

    public function actionDemo(): int
    {
        $managers = [];
        foreach ([['Ольга Коваль', 'olga@example.com'], ['Игорь Шевчук', 'igor@example.com']] as [$name, $email]) {
            $user = User::findOne(['email' => $email]);
            if (!$user) {
                $user = new User(['name' => $name, 'email' => $email, 'role' => User::ROLE_MANAGER, 'is_active' => true]);
                $user->setPassword('demo12345');
                $user->save(false);
            }
            $managers[] = $user;
        }

        $samples = [
            ['Анна Мельник', '+380501112233', 'website'],
            ['Дмитрий Бондаренко', '+380672223344', 'website'],
            ['Светлана Ткач', '+380933334455', 'instagram'],
            ['Максим Кравец', '+380504445566', 'website'],
            ['Наталья Лысенко', '+380665556677', 'google-ads'],
            ['Роман Савченко', '+380976667788', 'website'],
            ['Ирина Мороз', '+380637778899', 'facebook'],
            ['Павел Руденко', '+380508889900', 'website'],
        ];
        $flow = [
            [], [Lead::STATUS_IN_WORK], [Lead::STATUS_IN_WORK, Lead::STATUS_CALLBACK],
            [Lead::STATUS_IN_WORK, Lead::STATUS_SUCCESS], [Lead::STATUS_IN_WORK, Lead::STATUS_REJECTED],
            [Lead::STATUS_IN_WORK], [], [Lead::STATUS_IN_WORK, Lead::STATUS_CALLBACK, Lead::STATUS_SUCCESS],
        ];

        foreach ($samples as $i => [$name, $phone, $source]) {
            $result = LeadIntake::accept(['name' => $name, 'phone' => $phone, 'source' => $source]);
            if (empty($result['lead_id']) || !empty($result['duplicate'])) {
                continue;
            }
            $lead = Lead::findOne($result['lead_id']);
            foreach ($flow[$i] as $status) {
                $lead->changeStatus($status, null);
            }
        }

        $this->stdout("Демо-данные созданы. Менеджеры: olga@example.com / igor@example.com, пароль demo12345.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
