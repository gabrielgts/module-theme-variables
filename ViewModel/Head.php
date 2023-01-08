<?php
/** Copyright © Gtstudio X. All rights reserved. */

declare(strict_types=1);

namespace Gtstudio\ThemeVariables\ViewModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class Head implements ArgumentInterface
{
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ){}

    public function getConfig(string $field = '') : array|string|int|null
    {
        return $this->scopeConfig->getValue(
            rtrim("design/variables/$field", '/'),
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCssVariables() : string
    {
        $parsedCssVars = '';
        $fontFamily = $this->getConfig('variables_font_family');

        try {
            $variables = $this->serializer
                ->unserialize(
                    $this->getConfig('custom_theme_variables')
                );
            foreach ($variables as $variable) {
                if (empty($variable['key']) || empty($variable['value'])){
                    continue;
                }

                $varName = '--' . str_replace(' ', '-', $variable['key']);
                $parsedCssVars .= "$varName: {$variable['value']};" . PHP_EOL;
            }

            if (!empty($fontFamily)) {
                $parsedCssVars .= "--font-family: {$fontFamily};" . PHP_EOL;
            }
        } catch (\Exception|LocalizedException $exception) {
            $this->logger->error(
                __('Could not resolve css variables. Original message: {message}'),
                [
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                    'trace' => $exception->getTraceAsString()
                ]
            );
        }

        return $parsedCssVars;
    }
}
