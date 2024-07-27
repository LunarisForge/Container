<?php

namespace LunarisForge\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container
{
    /**
     * @var array<string, callable|string>
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * Bind an interface or class to a concrete implementation.
     *
     * @param string $abstract
     * @param callable|string $concrete
     */
    public function bind(string $abstract, callable|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Bind an interface or class to a singleton instance.
     *
     * @param string $abstract
     * @param mixed $concrete
     */
    public function singleton(string $abstract, mixed $concrete): void
    {
        $this->instances[$abstract] = $concrete;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @return mixed
     * @throws Exception
     */
    public function resolve(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            return $this->build($abstract);
        }

        $concrete = $this->bindings[$abstract];

        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Build the given type from the container.
     *
     * @param callable|string $concrete
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     */
    protected function build(callable|string $concrete): mixed
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        if (!class_exists($concrete)) {
            throw new Exception("Class {$concrete} does not exist.");
        }

        $reflector = new ReflectionClass($concrete);
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve the dependencies for a given set of parameters.
     *
     * @param array<ReflectionParameter> $parameters
     * @return array<int<0, max>, mixed>
     * @throws Exception
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if (!$type) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } elseif ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->resolve($type->getName());
            } else {
                $dependencies[] = $this->resolveNonClass($parameter);
            }
        }
        return $dependencies;
    }

    /**
     * Resolve a non-class dependency.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws Exception
     */
    protected function resolveNonClass(ReflectionParameter $parameter): mixed
    {
        $name = $parameter->getName();

        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if (isset($this->bindings[$name])) {
            return $this->resolve($name);
        }

        throw new Exception("Cannot resolve the dependency {$name}.");
    }
}
