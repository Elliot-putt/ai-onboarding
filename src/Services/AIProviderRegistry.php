<?php

namespace ElliotPutt\LaravelAiOnboarding\Services;

use ElliotPutt\LaravelAiOnboarding\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\App;

class AIProviderRegistry
{
    protected array $providers = [];
    protected bool $initialized = false;
    
    public function register(string $name, AIProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
    }
    
    public function getProvider(string $name): AIProviderInterface
    {
        $this->ensureInitialized();
        
        if (!isset($this->providers[$name])) {
            throw new \Exception("AI provider '{$name}' not found. Available providers: " . implode(', ', array_keys($this->providers)));
        }
        
        return $this->providers[$name];
    }
    
    public function getAvailableProviders(): array
    {
        $this->ensureInitialized();
        return array_keys($this->providers);
    }
    
    protected function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }
        
        $this->initialized = true;
    }
}
