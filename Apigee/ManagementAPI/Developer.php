<?php
/**
 * @file
 * Abstracts the Developer object in the Management API and allows clients to
 * manipulate it.
 *
 * @author djohnson
 */

namespace Apigee\ManagementAPI;

use \Apigee\Exceptions\ResponseException;
use \Apigee\Exceptions\ParameterException;

/**
 * Abstracts the Developer object in the Management API and allows clients to
 * manipulate it.
 *
 * @author djohnson
 */
 class Developer extends Base implements DeveloperInterface
{

    /**
     * The apps associated with the developer.
     * @var array
     */
    protected $apps;
    /**
     * @var string
     * The developer's email, used to unique identify the developer in Edge.
     */
    protected $email;
    /**
     * @var string
     * Read-only alternate unique ID. Useful when querying developer analytics.
     */
    protected $developerId;
    /**
     * @var string
     * The first name of the developer.
     */
    protected $firstName;
    /**
     * The last name of the developer.
     * @var string
     */
    protected $lastName;
    /**
     * @var string
     * The developer's username.
     */
    protected $userName;
    /**
     * @var string
     * The Apigee organization where the developer is regsitered.
     * This property is read-only.
     */
    protected $organizationName;
    /**
     * @var string
     * The developer status: 'active' or 'inactive'.
     */
    protected $status;
    /**
     * @var array
     * Name/value pairs used to extend the default profile.
     */
    protected $attributes;
    /**
     * @var int
     * Unix time when the developer was created.
     * This property is read-only.
     */
    protected $createdAt;
    /**
     * @var string
     * Username of the user who created the developer.
     * This property is read-only.
     */
    protected $createdBy;
    /**
     * @var int
     * Unix time when the developer was last modified.
     * This property is read-only.
     */
    protected $modifiedAt;
    /**
     * @var string
     * Username of the user who last modified the developer.
     * This property is read-only.
     */
    protected $modifiedBy;

    /* Accessors (getters/setters) */
    /**
     * {@inheritDoc}
     */
    public function getApps()
    {
        return $this->apps;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeveloperId()
    {
        return $this->developerId;
    }

    /**
     * {@inheritDoc}
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * {@inheritDoc}
     */
    public function setFirstName($fname)
    {
        $this->firstName = $fname;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * {@inheritDoc}
     */
    public function setLastName($lname)
    {
        $this->lastName = $lname;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * {@inheritDoc}
     */
    public function setUserName($uname)
    {
        $this->userName = $uname;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * {@inheritDoc}
     */
    public function setStatus($status)
    {
        if ($status === 0 || $status === FALSE) {
            $status = 'inactive';
        } elseif ($status === 1 || $status === TRUE) {
            $status = 'active';
        }
        if ($status != 'active' && $status != 'inactive') {
            throw new ParameterException('Status may be either active or inactive; value "' . $status . '" is invalid.');
        }
        $this->status = $status;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($attr)
    {
        if (array_key_exists($attr, $this->attributes)) {
            return $this->attributes[$attr];
        }
        return NULL;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($attr, $value)
    {
        $this->attributes[$attr] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Initializes default values of all member variables.
     *
     * @param \Apigee\Util\OrgConfig $config
     */
    public function __construct(\Apigee\Util\OrgConfig $config)
    {
        $this->init($config, '/o/' . rawurlencode($config->orgName) . '/developers');
        $this->blankValues();
    }

    /**
     * {@inheritDoc}
     */
    public function load($email)
    {
        $this->get(rawurlencode($email));
        $developer = $this->responseObj;
        self::loadFromResponse($this, $developer);
    }

   /**
    * Takes the raw KMS response and populates the member variables of the
    * passed-in Developer object from it.
    *
    * @param Apigee\ManagementAPI\Developer $developer
    * @param array $response
    */
   protected static function loadFromResponse(Developer &$developer, array $response)
    {
        $developer->apps = $response['apps'];
        $developer->email = $response['email'];
        $developer->developerId = $response['developerId'];
        $developer->firstName = $response['firstName'];
        $developer->lastName = $response['lastName'];
        $developer->userName = $response['userName'];
        $developer->organizationName = $response['organizationName'];
        $developer->status = $response['status'];
        $developer->attributes = array();
        if (array_key_exists('attributes', $response) && is_array($response['attributes'])) {
            foreach ($response['attributes'] as $attribute) {
                $developer->attributes[$attribute['name']] = $attribute['value'];
            }
        }
        $developer->createdAt = $response['createdAt'];
        $developer->createdBy = $response['createdBy'];
        $developer->modifiedAt = $response['lastModifiedAt'];
        $developer->modifiedBy = $response['lastModifiedBy'];
    }

    /**
     * {@inheritDoc}
     */
    public function validate($email = NULL)
    {
        if (!empty($email)) {
            try {
                $this->get(rawurlencode($email));
                return TRUE;
            } catch (ResponseException $e) {
            }
        }
        return FALSE;
    }

    /**
     * {@inheritDoc}
     */
    public function save($force_update = FALSE)
    {

        // See if we need to brute-force this.
        if ($force_update === NULL) {
            try {
                $this->save(TRUE);
            } catch (ResponseException $e) {
                if ($e->getCode() == 404) {
                    // Update failed because dev doesn't exist.
                    // Try insert instead.
                    $this->save(FALSE);
                } else {
                    // Some other response error.
                    throw $e;
                }
            }
            return;
        }

        if (!$this->validateUser()) {
            throw new ParameterException('Developer requires valid-looking email address, firstName, lastName and userName.');
        }

        $payload = array(
            'email' => $this->email,
            'userName' => $this->userName,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'status' => $this->status,
        );
        if (count($this->attributes) > 0) {
            $payload['attributes'] = array();
            foreach ($this->attributes as $name => $value) {
                $payload['attributes'][] = array('name' => $name, 'value' => $value);
            }
        }
        $url = NULL;
        if ($force_update || $this->createdAt) {
            if ($this->developerId) {
                $payload['developerId'] = $this->developerId;
            }
            $url = rawurlencode($this->email);
        }
        if ($force_update) {
            $this->put($url, $payload);
        } else {
            $this->post($url, $payload);
        }

        self::loadFromResponse($this, $this->responseObj);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($email = NULL)
    {
        $email = $email ? : $this->email;
        $this->http_delete(rawurlencode($email));
        if ($email == $this->email) {
            $this->blankValues();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function listDevelopers()
    {
        $this->get();
        $developers = $this->responseObj;
        return $developers;
    }

    /**
     * Returns an array of all developers in the org.
     *
     * @return array
     */
    public function loadAllDevelopers()
    {
        $this->get('?expand=true');
        $developers = $this->responseObj;
        $out = array();
        foreach ($developers['developer'] as $dev) {
            $developer = new Developer($this->config);
            self::loadFromResponse($developer, $dev);
            $out[] = $developer;
        }
        return $out;
    }

    /**
     * {@inheritDoc}
     */
    public function validateUser()
    {
        if (!empty($this->email) && (strpos($this->email, '@') > 0)) {
            $name = explode('@', $this->email, 2);
            if (empty($this->firstName)) {
                $this->firstName = $name[0];
            }
            if (empty($this->lastName)) {
                $this->lastName = $name[1];
            }
        }
        return (!empty($this->firstName) && !empty($this->lastName) && !empty($this->userName) && !empty($this->email) && strpos($this->email, '@') > 0);
    }

    /**
     * {@inheritDoc}
     */
    public function blankValues()
    {
        $this->apps = array();
        $this->email = NULL;
        $this->developerId = NULL;
        $this->firstName = NULL;
        $this->lastName = NULL;
        $this->userName = NULL;
        $this->organizationName = NULL;
        $this->status = NULL;
        $this->attributes = array();
        $this->createdAt = NULL;
        $this->createdBy = NULL;
        $this->modifiedAt = NULL;
        $this->modifiedBy = NULL;
    }


    /**
     * Converts this object's properties into an array for external use.
     *
     * @return array
     */
    public function toArray()
    {
        $properties = array_keys(get_object_vars($this));
        $excluded_properties = array_keys(get_class_vars(get_parent_class($this)));
        $output = array();
        foreach ($properties as $property) {
            if (!in_array($property, $excluded_properties)) {
                $output[$property] = $this->$property;
            }
        }
        $output['debugData'] = $this->getDebugData();
        return $output;
    }

    /**
     * Populates this object based on an incoming array generated by the
     * toArray() method above.
     *
     * @param $array
     */
    public function fromArray($array)
    {
        foreach ($array as $key => $value) {
            if (property_exists($this, $key) && $key != 'debugData') {
                $this->{$key} = $value;
            }
        }
        $this->loaded = TRUE;
    }

}