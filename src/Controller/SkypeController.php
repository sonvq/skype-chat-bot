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

        $this->replyToUser($service_url, $conversation, $activity_id, $bot, $user, $textOutput, $access_token);

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function replyToUser($service_url, $conversation, $activity_id, $bot, $user, $textOutput, $access_token)
    {
        $client_reply_message = new Client();
        $url = $service_url . '/v3/conversations/' . $conversation['id'] . '/activities/' . $activity_id;
        try {
            $client_reply_message->request(
                'POST',
                $url,
                [
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
                ]
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
     * @return string
     */
    protected function createTextOutput($text)
    {
        // DEFAULT TEXT
        $textOutput = '(hysterical) Sorry, this is not a valid command, below are all available commands (hysterical)
* **help**
*
* **build**
*
* **holiday**  
*
* **link**
*
* **wifi**
';

        // HELP
        if (in_array($text, ['help', 'ê', 'hello', 'hi'])) {
            $textOutput = '
(hearteyes) Below are all available commands (hearteyes)
* **help**
*
* **build**
*
* **holiday**
*
* **link**
*
* **wifi**
';
        }

        // WIFI
        if (in_array($text, ['wifi'])) {
            $textOutput = '
(idea) Below are password for CC wifi (idea)
* **1 - wifi coccoc.vn-2-guest**    
    guesttest123
*
* **2 - wifi coccoc.vn-2**    
    uBq%D*Ei
*
';
        }

        // LINK
        if (in_array($text, ['link'])) {
            $textOutput = '
(rainbowsmile) Below are all useful links for QC (rainbowsmile)
* **1 - [Staff tool](https://stafftool.coccoc.com/user/login)**    
*
* **2 - [Staging QC](http://staging-1-qc.coccoc.com/)**    
*
* **3 - [Jira](https://coccoc.atlassian.net/secure/Dashboard.jspa)**    
*
* **4 - [Ads-data-management service API Doc Liam](https://docs.google.com/document/d/1bIq8qquqaMubuCLru21d9I9GhmY_ewaxl1yogOcBHoI/edit#)**
*
* **5 - [Teamcity](http://qc-teamcity.coccoc.com/agents.html)**
*
* **6 - [Git QC](https://git.itim.vn/users/sign_in?redirect_to_referer=yes)**
*
* **7 - [Trac](https://trac.coccoc.com/coccoc/)**
';
        }

        // BUILD, DEPLOY COMMAND
        if (in_array($text, ['build', 'deploy'])) {
            $textOutput = '
(stareyes) Below are deploy commands (stareyes)
* **1 - Merge branch into master (please change to your branch)**
git merge --squash origin/feature/543884-addPredisLibrary
*
* **2 - Commit and push code**
*
* **3 - Describe the latest tag**
    git describe --tags --abbrev=0  
*
* **4 - Create new tag**
    git tag 0.0.10 
*
* **5 - Push the tag**
    git push --tags
*
* **6 - Modify the changelog**
    gbp dch --debian-tag="%(version)s" -S  --git-author -N "$(git describe --tags --abbrev=0)" 
*
* **7 - Commit and push the changelog**
    git commit -am "updated changelog"
    git push
*    
* **8 - Deploy in teamcity** 
    Visit http://qc-teamcity.coccoc.com/overview.html
    Deploy on Staging => Run
*
* **9 - Login and check the build**
    ssh ads2v.dev.itim.vn
    dpkg -l | grep qc-user
    tail -f /var/log/apt/history.log
';
        }

        // HOLIDAY
        if (in_array($text, ['holiday', 'lịch nghỉ'])) {
            $textOutput = '
(party) CÁC NGÀY LỄ ĐƯỢC NGHỈ NĂM 2019 (party)
* **1 - Tết dương lịch (01/01):**
    nghỉ 04 ngày (29/12/2018-01/01/2019); 
    làm bù 01 ngày thứ bảy (05/01/2019)
*
* **2 - Tết Nguyên đán (02/02-10/02/2019):**
    nghỉ 09 ngày
*
* **3 - Giỗ Tổ Hùng Vương:**
    nghỉ 03 ngày (13-15/04/2019)
*
* **4 - Ngày lễ chiến thắng và Quốc tế lao động (30/4 – 01/5):** 
    nghỉ 05 ngày (27/04-01/05/2019); 
    làm bù 01 ngày thứ bảy (04/05/2019)
*
* **5 - Ngày Quốc khánh (02/09)**
    nghỉ 03 ngày (31/08-01/09/2019)
';
        }
        return $textOutput;
    }
}
