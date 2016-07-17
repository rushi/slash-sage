<?php

class Slack
{
    private $logger;
    private $settings = [];
    private $params = [];

    private $requiredFields = ['user_name', 'token', 'text'];

    public function __construct(Psr\Log\LoggerInterface $logger, array $settings = [])
    {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    /**
     * Authenticate the message from Slack
     *
     * @param array $params
     * @return int HTTP Code
     */
    public function authenticateMessage(array $params)
    {
        foreach ($this->requiredFields as $field) {
            if (!isset($params[$field])) {
                $this->logger->warning("Invalid request. Misisng field", ['field' => $field]);
                return 400;
            }
        }

        if (!empty($this->settings['allowed_users']) && !in_array($params['user_name'], $this->settings['allowed_users'])) {
            $this->logger->warning("Invalid user, not honoring request", ['user' => $params['user_name']]);
            return 403;
        }

        if (!empty($this->settings['token']) && $this->settings['token'] != $params['token']) {
            $this->logger->warning("Invalid token, not honoring request", ['token' => $params['token']]);
            return 403;
        }

        $this->params = $params;

        return 200;
    }

    public function getCommand()
    {
        return $this->clean($this->getText()[0]);
    }

    public function getTextArg($pos = 1)
    {
        $text = $this->getText();
        if (isset($text[$pos])) {
            return $this->clean($text[$pos]);
        }

        return null;
    }

    public function getUserName()
    {
        return $this->params['user_name'];
    }

    /**
     * Return true if the message should be posted in channel
     *
     * @return boolean True if message should be posted in channel
     */
    public function shouldPostInChannel()
    {
        $text = $this->getText();
        return $this->clean($text[count($text) - 1]) == 'pub'; // Check if the last argument is pub
    }

    private function getText()
    {
        return explode(" ", trim($this->params['text']));
    }

    private function clean($str)
    {
        return strtolower(trim($str));
    }
}
