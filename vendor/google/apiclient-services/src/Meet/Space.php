<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Meet;

class Space extends \Google\Model
{
  protected $activeConferenceType = ActiveConference::class;
  protected $activeConferenceDataType = '';
  protected $configType = SpaceConfig::class;
  protected $configDataType = '';
  /**
   * @var string
   */
  public $meetingCode;
  /**
   * @var string
   */
  public $meetingUri;
  /**
   * @var string
   */
  public $name;

  /**
   * @param ActiveConference
   */
  public function setActiveConference(ActiveConference $activeConference)
  {
    $this->activeConference = $activeConference;
  }
  /**
   * @return ActiveConference
   */
  public function getActiveConference()
  {
    return $this->activeConference;
  }
  /**
   * @param SpaceConfig
   */
  public function setConfig(SpaceConfig $config)
  {
    $this->config = $config;
  }
  /**
   * @return SpaceConfig
   */
  public function getConfig()
  {
    return $this->config;
  }
  /**
   * @param string
   */
  public function setMeetingCode($meetingCode)
  {
    $this->meetingCode = $meetingCode;
  }
  /**
   * @return string
   */
  public function getMeetingCode()
  {
    return $this->meetingCode;
  }
  /**
   * @param string
   */
  public function setMeetingUri($meetingUri)
  {
    $this->meetingUri = $meetingUri;
  }
  /**
   * @return string
   */
  public function getMeetingUri()
  {
    return $this->meetingUri;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Space::class, 'Google_Service_Meet_Space');