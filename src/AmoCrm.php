<?php

namespace App;

final class AmoCrm
{
    private const TEXT = 'Сделка без задачи';
    private $cookie_file;
    private $email;
    private $subdomain;
    private $key;

    public function __construct()
    {
        $this->cookie_file = __DIR__ . '/../public/cookie.txt';
    }

    /**
     * Добавляем данные для авторизации
     * @param string $email
     * @param string $subdomain
     * @param string $key
     */
    public function set(string $email, string $subdomain, string $key): void
    {
        $this->email = $email;
        $this->subdomain = $subdomain;
        $this->key = $key;
    }

    /**
     * Авторизация по API в системе AMOCRM
     */
    public function auth(): void
    {
        $data = [
            'USER_LOGIN' => $this->email,
            'USER_HASH' => $this->key
        ];
        #Формируем ссылку для запроса
        $link = 'https://' . $this -> subdomain . '.amocrm.ru/private/api/auth.php?type=json';
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $link);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);
            $out = json_decode(curl_exec($curl), true)['response'];
            curl_close($curl);
            if (!isset($out['auth']) || $out['auth'] == false) {
                throw new \InvalidArgumentException('Ошибка авторизации');
            }
        } catch (\InvalidArgumentException $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * Достаем список сделок без задач, где closest_task_at=0
     * @return array
     */
    public function getLeedsWithoutOpenTasks(): array
    {
        $link = 'https://' . $this -> subdomain . '.amocrm.ru/api/v2/leads';
        $curl = curl_init($link);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);
        $out = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($out, true);
        $response = $response['_embedded']['items'];
        $leads = [];
        foreach ($response as $task) {
            if ($task['closest_task_at'] == 0) {
                $leads[] = $task['id'];
            }
        }
        return $leads;
    }

    /**
     * Добавляем задачу в сделку по ID
     * @param array $leads
     * @throws \Exception
     */
    public function addTaskForLeads(array $leads): void
    {
        if (count($leads) == 0) {
            throw new \Exception('Нет сделок без открытых задач');
        }
        $task = [];
        foreach ($leads as $lead) {
            $task[] = [
                'element_id' => $lead,
                'element_type' => 2,
                'task_type' => mt_rand(1, 3),
                'text' => self::TEXT,
                'created_at' => time(),
            ];
        }
        $data['add'] = $task;
        $link = 'https://' . $this -> subdomain . '.amocrm.ru/api/v2/tasks';
        $curl = curl_init($link);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_exec($curl);
        curl_close($curl);
    }
}
