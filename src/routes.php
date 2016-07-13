<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/Sage.php';
$sage = new Sage($container['guzzle'], $container['logger']);

$app->post('/slack', function (Request $request, Response $response) use ($sage) {
    $params = $request->getParsedBody();
    $this->logger->info("Slack Request", $params);

    $requiredFields = ['user_name', 'token', 'text'];
    foreach ($requiredFields as $field) {
        if (!isset($params[$field])) {
            $this->logger->warn("Invalid request. Misisng field", ['field' => $field]);
            return $response->withStatus(400);
        }
    }

    $slackSettings = $this->get('settings')['slack'];
    if (!empty($slackSettings['allowed_users']) && !in_array($params['user_name'], $slackSettings['allowed_users'])) {
        $this->logger->warn("Invalid user, not honoring request", ['user' => $params['user_name']]);
        return $response->withStatus(403); // Silently fail
    }

    if (!empty($slackSettings['token']) && $slackSettings['token'] != $params['token']) {
        $this->logger->warn("Invalid token, not honoring request", ['token' => $params['token']]);
        return $response->withStatus(403); // Silently fail
    }

    $sage->setUser($params['user_name']);
    $args = explode(" ", trim($params['text']));
    $inChannel = false;
    if (strtolower($args[count($args)-1]) == "pub") {
        // last argument is 'pub' which means output should go in channel.
        array_pop($args); // remove that last arg, we don't need it anymore
        $inChannel = true;
    }

    $body = [];
    switch (strtolower($args[0])) {
        case 'agent':
        case 'agents':
            $agentName = null;
            if (isset($args[1])) {
                $agentName = strtolower(trim($args[1]));
            }
            $body = $sage->getAgents($agentName);
            break;

        case 'status':
        case 'st':
            if (isset($args[1])) {
                $body = $sage->getPipelineInfo($args[1]);
            } else {
                $body = ["text" => "Pipeline name required"];
            }
            break;

        case 'search':
            if (isset($args[1])) {
                $pipeline = $sage->searchPipeline($args[1]);
                if (!is_null($pipeline)) {
                    $body = $sage->getPipelineInfo($pipeline);
                } else {
                    $body = ['text' => 'No pipeline found for ' . $args[1]];
                }
            } else {
                $body = ["text" => "Search string required"];
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

    $body['response_type'] = ($inChannel) ? "in_channel" : "ephemeral";
    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write(json_encode($body));

    return $response;
});

//
// These routes are just for testing, to be removed
//
$app->get('/agents', function (Request $request, Response $response) use ($sage) {
    return $response->getBody()->write(json_encode($sage->getAgents()));
});

$app->get('/pipeline/{pipeline}', function (Request $request, Response $response) use ($sage) {
    $responseTxt = $sage->getPipelineInfo($request->getAttribute('pipeline'));
    return $response->withStatus(200)->getBody()->write(nl2br($responseTxt));
});
