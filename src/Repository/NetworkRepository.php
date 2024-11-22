<?php

namespace App\Repository;

use App\Entity\HttpMethods;
use DateTime;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NetworkRepository
{
    private static string $URI_AUTH = 'https://developer-api.m4.systems:4443/api_auth';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    /**
     * @return string successful login or error message
     * @throws TransportExceptionInterface
     */
    public function login(string $username, string $password): string
    {

        try {
            $response = $this->httpClient->request(
                HttpMethods::POST->value,
                self::$URI_AUTH . '/login_check',
                [
                    'json' => [
                        'username' => $username,
                        'password' => $password,
                    ],
                ]
            );

            $content = $response->toArray();

            $GLOBALS['USER_ID'] = $content['data']['id'];
            $GLOBALS['TOKEN'] = $content['token'];

            foreach ($content['services'] as $services) {
                $GLOBALS['SERVICES'][$services['code']] = $services['apiUrl'];
            }

            return "You successfully login in your account.";
        } catch (ClientExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'client-side', function: __FUNCTION__);
        } catch (DecodingExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'decoding', function: __FUNCTION__);
        } catch (RedirectionExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'redirect', function: __FUNCTION__);
        } catch (ServerExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'server-side', function: __FUNCTION__);
        } catch (TransportExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'transport', function: __FUNCTION__);
        } catch (Exception $e) {
            printf('<pre>');
            printf($response->getInfo('debug'));
            printf('</pre>');
            return "Some error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        }
    }

    /**
     * @param int $userId
     * @return string
     * @throws TransportExceptionInterface
     */
    public function logout(): string
    {
        try {
            $this->httpClient->request(
                HttpMethods::POST->value,
                self::$URI_AUTH,
                [
                    'json' =>
                        [
                            'method' => 'logout',
                            'id' => $GLOBALS['USER_ID'],
                            'jsonrpc' => '2.0'
                        ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $GLOBALS['TOKEN']
                    ],
                ]
            );

            $GLOBALS['USER_ID'] = null;
            $GLOBALS['TOKEN'] = null;
            $GLOBALS['SERVICES'] = null;
            $GLOBALS['SECOND_TASK_ID'] = null;

            return 'You successfully logout from your account';
        } catch (ClientExceptionInterface $e) {
            return "Some client-side error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        } catch (DecodingExceptionInterface $e) {
            return "Some decoding error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        } catch (RedirectionExceptionInterface $e) {
            return "Some redirect error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        } catch (ServerExceptionInterface $e) {
            return "Some server-side error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        } catch (TransportExceptionInterface $e) {
            return "Some transport error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        } catch (Exception $e) {
            return "Some error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        }

    }

    public function getStatuses()
    {
        try {
            $response = self::makeRequest(
                apiUrl: $GLOBALS['SERVICES']['SD'],
                method: 'M4GetStatus',
                params: []
            );

            $content = $response->toArray();

            for ($i = 0; $i < 5; $i++) {
                $index = random_int(1, count($content['result']));

                $GLOBALS['STATUSES'][$i] = $content['result'][$index - 1]['id'];
            }

        } catch (ClientExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'client-side', function: __FUNCTION__);
        } catch (DecodingExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'decoding', function: __FUNCTION__);
        } catch (RedirectionExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'redirect', function: __FUNCTION__);
        } catch (ServerExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'server-side', function: __FUNCTION__);
        } catch (TransportExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'transport', function: __FUNCTION__);
        } catch (Exception $e) {
            printf('<pre>');
            printf($response->getInfo('debug'));
            printf('</pre>');
            return "Some error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        }
    }

    public function getTasks(DateTime $dateTime, array $statuses): string
    {
        try {
            $response = self::makeRequest(
                params: [
                    'status' => $statuses,
                    'lastUpdate' => $dateTime->format('d.m.Y H:i:s'),
                ],
                method: 'M4GetTasks',
                apiUrl: $GLOBALS['SERVICES']['SD']
            );

            $content = $response->toArray();

            if (isset($content['error'])) {
                return $content['error']['message'];
            } else {
                if ($content['result']) {
                    if (count($content['result']) > 1)
                        $GLOBALS['SECOND_TASK_ID'] = $content['result'][1]['taskId'];
                    else
                        $GLOBALS['SECOND_TASK_ID'] = $content['result'][0]['taskId'];

                    return $response->getContent();
                } else {
                    return 'Nothing return';
                }
            }

        } catch (ClientExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'client-side', function: __FUNCTION__);
        } catch (DecodingExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'decoding', function: __FUNCTION__);
        } catch (RedirectionExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'redirect', function: __FUNCTION__);
        } catch (ServerExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'server-side', function: __FUNCTION__);
        } catch (TransportExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'transport', function: __FUNCTION__);
        } catch (Exception $e) {
            printf('<pre>');
            printf($response->getInfo('debug'));
            printf('</pre>');
            return "Some error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        }
    }

    public function getTaskDetails(int $taskId)
    {
        try {
            $response = self::makeRequest(
                params: ['taskId' => $taskId],
                apiUrl: $GLOBALS['SERVICES']['SD'],
                method: 'M4GetTaskDetails'
            );

            $content = $response->toArray();

            if (isset($content['error'])) {
                return $content['error']['message'];
            } else {
                return $response->getContent();
            }

        } catch (ClientExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'client-side', function: __FUNCTION__);
        } catch (DecodingExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'decoding', function: __FUNCTION__);
        } catch (RedirectionExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'redirect', function: __FUNCTION__);
        } catch (ServerExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'server-side', function: __FUNCTION__);
        } catch (TransportExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'transport', function: __FUNCTION__);
        } catch (Exception $e) {
            printf('<pre>');
            printf($response->getInfo('debug'));
            printf('</pre>');
            return "Some error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        }
    }

    public function addCommentToTask(int $taskId, string $comment)
    {
        try {
            $response = self::makeRequest(
                params: [
                    'taskId' => $taskId,
                    'comment' => $comment,
                    'isPublis' => true
                ],
                method: 'M4AddTaskComment',
                apiUrl: $GLOBALS['SERVICES']['SD']
            );

            $content = $response->toArray();

            if (isset($content['error'])) {
                return $content['error']['message'];
            } else {
                return $response->getContent();
            }

        } catch (ClientExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'client-side', function: __FUNCTION__);
        } catch (DecodingExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'decoding', function: __FUNCTION__);
        } catch (RedirectionExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'redirect', function: __FUNCTION__);
        } catch (ServerExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'server-side', function: __FUNCTION__);
        } catch (TransportExceptionInterface $e) {
            return self::returnError(response: $response, exception: $e, errorType: 'transport', function: __FUNCTION__);
        } catch (Exception $e) {
            printf('<pre>');
            printf($response->getInfo('debug'));
            printf('</pre>');
            return "Some error happened in " . __FUNCTION__ . ". Error is " . $e->getMessage();
        }
    }

    private function makeRequest(array $params, string $method, string $apiUrl): ResponseInterface
    {
        return $this->httpClient->request(
            HttpMethods::POST->value,
            $apiUrl, [
                'json' => [
                    'id' => $GLOBALS['USER_ID'],
                    'jsonrpc' => '2.0',
                    'method' => $method,
                    'params' => $params,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $GLOBALS['TOKEN']
                ],
            ]
        );
    }

    private function returnError(ResponseInterface $response, ExceptionInterface $exception, string $errorType, string $function): string
    {
        printf('<pre>');
        printf($response->getInfo('debug'));
        printf('</pre>');
        return "Some $errorType error happened in $function. Error is " . $exception->getMessage();
    }
}
