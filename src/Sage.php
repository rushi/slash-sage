<?php
use GuzzleHttp\Exception\RequestException;

class Sage
{
    private $guzzle;
    private $logger;
    private $user;

    public function __construct(GuzzleHttp\Client $guzzle, Psr\Log\LoggerInterface $logger)
    {
        $this->guzzle = $guzzle;
        $this->logger = $logger;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getAgents($agentName = null)
    {
        try {
            $response = $this->guzzle->get('agents', ['headers' => ['Accept' => 'application/vnd.go.cd.v1+json']]);
            $statusCode = $response->getStatusCode();
            $this->logger->info("Pipelines API call", ['agentName' => $agentName, 'status' => $statusCode]);
        } catch (Exception $e) {
            return ["text" => $e->getMessage()];
        }

        $payload = ["username" => "Sage", "attachments" => []];
        $content = json_decode($response->getBody(), true);
        $agents = [];
        foreach ($content['_embedded']['agents'] as $agent) {
            if (in_array($agent['hostname'], $agents)) continue;
            if (!is_null($agentName) && $agent['hostname'] != $agentName) continue;
            $agents[] = $agent['hostname'];

            $freeSpace = round($agent['free_space'] / (1073741824), 2); // B to GB
            $color = ($freeSpace > 5) ? 'good' : (($freeSpace < 2) ? 'danger' : 'warning');
            if ($agent['status'] == "Disabled") {
                $color = "#CCCCCC";
            }

            $payload["attachments"][] = [
                "color" => $color,
                "pretext" => sprintf("*%s* (%s)", $agent['hostname'], $agent['operating_system']),
                "fallback" => sprintf("%s %s GB", $agent['hostname'], $freeSpace),
                "mrkdwn_in" => ["pretext"],
                "fields" => [
                    ["title" => "Status", "value" => $agent['status'], "short" => true],
                    ["title" => "Free Space", "value" => $freeSpace . " GB", "short" => true]
                ]
            ];
        }

        return $payload;
    }

    public function getPipelineInfo($pipelineName)
    {
        try {
            $response = $this->guzzle->get("pipelines/$pipelineName/history");
            $statusCode = $response->getStatusCode();
            $this->logger->info("Pipelines API call", ['pipeline' => $pipelineName, 'status' => $statusCode]);
        } catch (RequestException $re) {
            $response = $re->getResponse();
            if ($response->getStatusCode() == 404) {
                return ["text" => "Pipline $pipelineName not found"];
            }

            return ["text" => $re->getMessage()];
        }
        catch (Exception $e) {
            return ["text" => $e->getMessage()];
        }

        $max = 2;
        $content = json_decode($response->getBody(), true);
        $payload = ["username" => "Sage", "attachments" => []];
        foreach ($content['pipelines'] as $pipeline) {
            $isSuccess = true;
            $attachment = ["pretext" => "*$pipelineName* Build #" . $pipeline['label'], "mrkdwn_in" => ["pretext"], "fields" => []];
            foreach ($pipeline['stages'] as $stage) {
                if (!isset($stage['result'])) {
                    continue; // stage has not been run, skip it
                }

                $result = $stage["result"];
                if ($result == "Failed") {
                    foreach ($stage['jobs'] as $job) {
                        if ($job['result'] != "Passed") {
                            $result .= sprintf("\n ✘ %s", $job['name']);
                            $isSuccess = false;
                        }
                    }
                }
                $result = ($result == "Unknown") ? "Building" : $result;
                $attachment["fields"][] = ["title" => $stage["name"], "value" => $result, "short" => true];
            }

            $attachment['color'] = ($isSuccess) ? 'good' : 'danger';
            $payload['attachments'][] = $attachment;

            $max--;
            if ($max === 0) {
                break;
            }
        }

        $payload['attachments'] = array_reverse($payload['attachments']); // Display latest run at the bottom

        return $payload;
    }

    public function searchPipeline($pipeline)
    {
        // Find closest matching pipeline from the dashboard
        try {
            $response = $this->guzzle->get('dashboard', ['headers' => ['Accept' => 'application/vnd.go.cd.v1+json']]);
            $statusCode = $response->getStatusCode();
            $this->logger->info("Dashboard API call", ['pipeline' => $pipeline, 'status' => $statusCode]);

            $content = json_decode($response->getBody(), true);
            foreach ($content["_embedded"]["pipeline_groups"] as $pipelineGroups) {
                foreach ($pipelineGroups["_embedded"]["pipelines"] as $_pipeline) {
                    if (isset($_pipeline["name"]) && preg_match("/$pipeline/i", $_pipeline["name"])) {
                        return $_pipeline["name"];
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->warn("Error while searching for pipeline", ["pipeline" => $pipeline, "exception" => $e->getMessage()]);
            return $pipeline;
        }

        return $pipeline;
    }
}
