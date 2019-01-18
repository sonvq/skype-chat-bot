<?php

namespace App\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class SkypeController
 * @package App\Controller
 */
class SkypeController
{
    /**
     * @var string
     */
    private $botName = 'ads management wiki ';

    /**
     * @var string
     */
    private $botSecondName = 'standup meeting ';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SkypeController constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public function index() {
        echo 'Hello World';
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function message(Request $request, Response $response, $args)
    {
        $params = $request->getParsedBody();

        $text = str_replace([$this->botName, $this->botSecondName], '', strtolower($params['text']));

        $text = preg_replace('/\s+/', '', $text);

        $this->logger->info('Received a chat message: ' . print_r($params,true));

        $conversation = $params['conversation'];
        $bot = $params['recipient'];
        $user = $params['from'];
        $activity_id = $params['id'];
        $service_url = $params['serviceUrl'];

        $client_request_token = new Client();

        $access_token = $this->requestSkypeAccessToken($client_request_token);

        $textOutput = $this->createTextOutput($text);
        $attachment = $this->createAttachment($text);

        $this->replyToUser($service_url, $conversation, $activity_id, $bot, $user, $textOutput, $access_token, $attachment);

        return $response->withJson(['message' => 'ok'], 200);
    }

    /**
     * @param Client $client_request_token
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function requestSkypeAccessToken(Client $client_request_token)
    {
        $access_token = json_decode($client_request_token->request(
            'POST',
            'https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token',
            [
                'form_params' =>
                    [
                        'grant_type' => 'client_credentials',
                        'client_id' => 'bba0a69a-933c-498e-9771-c52176dd6ec0',
                        'client_secret' => 'zguxATJ92*|ftvQBPV865?)',
                        'scope' => 'https://api.botframework.com/.default',
                    ],
                'headers' =>
                    [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
            ]
        )->getBody()->getContents())->access_token;
        return $access_token;
    }

    /**
     * @param $service_url
     * @param $conversation
     * @param $activity_id
     * @param $bot
     * @param $user
     * @param $textOutput
     * @param $access_token
     * @param $attachment
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function replyToUser($service_url, $conversation, $activity_id, $bot, $user, $textOutput, $access_token, $attachment)
    {
        $client_reply_message = new Client();
        $url = $service_url . '/v3/conversations/' . $conversation['id'] . '/activities/' . $activity_id;
        $body = [
            'json' =>
                [
                    'type' => 'message',
                    'from' => [
                        'id' => $bot['id'],
                        'name' => $bot['name'],
                    ],
                    'conversation' => [
                        'id' => $conversation['id'],
                    ],
                    'recipient' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                    ],
                    'text' => $textOutput,
                    'replyToId' => $activity_id,
                ],
            'headers' =>
                [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                ],
        ];

        if (!empty($attachment)) {
            $body['json']['attachments'] = $attachment;
        }

        try {
            $client_reply_message->request(
                'POST',
                $url,
                $body
            );
        } catch (TransferException $e) {
            $this->logger->info(Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                $this->logger->info(Psr7\str($e->getResponse()));
            }
        }
    }

    /**
     * @param $text
     * @return array
     */
    protected function createAttachment($text)
    {
        $attachment = [];

        if (in_array($text, ['nope', 'early'])) {
            $attachment = [
                'contentType' => 'image/png',
                'contentUrl' => 'data:image/jpeg;base64,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',
                'name' => 'nope.jpg',
            ];
        }

        return $attachment;
    }

    /**
     * @param $text
     * @return string
     */
    protected function createTextOutput($text)
    {
        // DEFAULT TEXT
        $textOutput = '
(hysterical) Sorry, this is not a valid command, below are all available commands (hysterical)
**help**
**build**
**holiday**
**link**
**wifi**
';

        // HELP
        if (in_array($text, ['help', 'ê', 'hello', 'hi'])) {
            $textOutput = '
(hearteyes) Below are all available commands (hearteyes)
**help**
**build**
**holiday**
**link**
**wifi**
';
        }

        // WIFI
        if (in_array($text, ['wifi'])) {
            $textOutput = '
(idea) Below are password for CC wifi (idea)
**1 - wifi coccoc.vn-2-guest**
    guesttest123

**2 - wifi coccoc.vn-2**
    uBq%D*Ei
';
        }

        // NOPE
        if (in_array($text, ['nope', 'early'])) {
            $textOutput = '';
        }

        // LINK
        if (in_array($text, ['link'])) {
            $textOutput = '
(rainbowsmile) Below are all useful links for QC (rainbowsmile)
**1 - [Staff tool](https://stafftool.coccoc.com/user/login)**
**2 - [Staging QC](http://staging-1-qc.coccoc.com/)**
**3 - [Jira](https://coccoc.atlassian.net/secure/Dashboard.jspa)**
**4 - [Ads-data-management service API Doc Liam](https://docs.google.com/document/d/1bIq8qquqaMubuCLru21d9I9GhmY_ewaxl1yogOcBHoI/edit#)**
**5 - [Teamcity](http://qc-teamcity.coccoc.com/agents.html)**
**6 - [Git QC](https://git.itim.vn/users/sign_in?redirect_to_referer=yes)**
**7 - [Trac](https://trac.coccoc.com/coccoc/)**
';
        }

        // BUILD, DEPLOY COMMAND
        if (in_array($text, ['build', 'deploy'])) {
            $textOutput = '
(stareyes) Below are deploy commands (stareyes)
**1 - Merge branch into master (please change to your branch)**
    git merge --squash origin/feature/543884-addPredisLibrary

**2 - Commit and push code**

**3 - Describe the latest tag**
    git describe --tags --abbrev=0  

**4 - Create new tag**
    git tag 0.0.10 

**5 - Push the tag**
    git push --tags

**6 - Modify the changelog**
    gbp dch --debian-tag="%(version)s" -S  --git-author -N "$(git describe --tags --abbrev=0)" 

**7 - Commit and push the changelog**
    git commit -am "updated changelog"
    git push
 
**8 - Deploy in teamcity** 
    Visit http://qc-teamcity.coccoc.com/overview.html
    Deploy on Staging => Run

**9 - Login and check the build**
    ssh ads2v.dev.itim.vn
    dpkg -l | grep qc-user
    tail -f /var/log/apt/history.log
';
        }

        // HOLIDAY
        if (in_array($text, ['holiday', 'lịch nghỉ'])) {
            $textOutput = '
(party) CÁC NGÀY LỄ ĐƯỢC NGHỈ NĂM 2019 (party)
**1 - Tết dương lịch (01/01):**
    nghỉ 04 ngày (29/12/2018-01/01/2019); 
    làm bù 01 ngày thứ bảy (05/01/2019)

**2 - Tết Nguyên đán (02/02-10/02/2019):**
    nghỉ 09 ngày

**3 - Giỗ Tổ Hùng Vương:**
    nghỉ 03 ngày (13-15/04/2019)

**4 - Ngày lễ chiến thắng và Quốc tế lao động (30/4 – 01/5):** 
    nghỉ 05 ngày (27/04-01/05/2019); 
    làm bù 01 ngày thứ bảy (04/05/2019)

**5 - Ngày Quốc khánh (02/09)**
    nghỉ 03 ngày (31/08-01/09/2019)
';
        }
        return $textOutput;
    }
}
