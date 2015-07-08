<?php

namespace Imagecraft\Layer;

/**
 * @author Xianghan Wang <coldume@gmail.com>
 *
 * @since  1.0.0
 */
interface ParameterBagInterface
{
    /**
     * @param string $name
     * @param mixed  $value
     */
    public function set($name, $value);

    /**
     * @param mixed[] $parameters
     *
     * @return $this
     */
    public function add(array $parameters);

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     *
     *  @return $this
     */
    public function remove($name);

    /**
     *  @return $this
     */
    public function clear();
}
