<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reverted custom session wrapper: replacing the session manager in the
        // container broke middleware type expectations (StartSession). Tests that
        // flush the session should explicitly call $this->refreshApplication()
        // (or otherwise refresh the auth state) after flushing the session to
        // simulate session expiration / logout behavior.
    }
}
