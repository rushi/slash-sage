<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/Sage.php';
$sage = new Sage($container['guzzle'], $container['logger']);

require_once __DIR__ . '/Slack.php';
$slack = new Slack($container['logger'], $container['settings']->get('slack'));

$app->post('/slack', function (Request $request, Response $response) use ($sage, $slack) {
    $params = $request->getParsedBody();
    $this->logger->info("Slack Request", $params);

    $authCode = $slack->authenticateMessage($params);
    if ($authCode !== 200) {
        return $response->withStatus($authCode);
    }

    $body = [];
    switch ($slack->getCommand()) {
        case 'agent':
        case 'agents':
            $body = $sage->getAgents($slack->getTextArg());
            break;

        case 'st':
        case 'status':
            $body = ["text" => "Pipeline name required"];
            if (!is_null($slack->getTextArg())) {
                $body = $sage->getPipelineInfo($slack->getTextArg());
            }
            break;

        case 'search':
            $body = ["text" => "Search string required"];
            if (!is_null($slack->getTextArg())) {
                $pipeline = $sage->searchPipeline($slack->getTextArg());
                $body = ['text' => 'No pipeline found for ' . $slack->getTextArg()];
                if (!is_null($pipeline)) {
                    $body = $sage->getPipelineInfo($pipeline);
                }
            }
            break;

        case 'help':
        default:
            $commands = [
                "• `" . $params['command'] . " agents (optional_agent_name)` List all agent(s) & their status",
                "• `" . $params['command'] . " status (required_pipeline_name)` Show build status for this pipeline",
                "• `" . $params['command'] . " search (required_search_re)` Search for pipeline using regex and show build status"
            ];
            $body = ["text" => "Available commands are:\n" . implode("\n", $commands)];
            break;
    }

    $body['response_type'] = ($slack->shouldPostInChannel()) ? "in_channel" : "ephemeral";
    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write(json_encode($body));

    return $response;
});
