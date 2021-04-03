<?php


namespace App\Entity;


use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Entity\Contracts\UUIDDBElementInterface;
use App\Entity\Embeddable\Address;
use App\Validator\PersonalEmailAddress;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\IdGenerator\UuidV4Generator;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\EntityListeners({"App\EntityListener\FillSecretEntityListener"})
 * @UniqueEntity(fields={"email"}, message="validator.email_already_used")
 */
class PostalVotingRegistration implements UUIDDBElementInterface, TimestampedElementInterface
{
    use TimestampTrait;


    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidV4Generator::class)
     */
    private $id;

    /**
     * @var string The email address of the student, where the verification will be send.
     * @ORM\Column(type="string", unique=true)
     * @Assert\Regex("/^.*@uni-jena.de$/", message="validator.must_be_uni_jena_address")
     * @Assert\Email(mode="strict")
     * @PersonalEmailAddress()
     * @Assert\NotBlank()
     */
    private $email = "";

    /**
     * @var string The first name(s) of the student.
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $first_name = "";

    /**
     * @var string The last name of the student
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $last_name;

    /**
     * @var string The student number of the student
     * @ORM\Column(type="string")
     * @Assert\Regex("/^\d{1,6}$/", message="validator.invalid_student_number")
     * @Assert\NotBlank()
     */
    private $student_number;

    /**
     * @var Address The address where the voting kit should be sent to
     * @Assert\Valid()
     * @ORM\Embedded(class="\App\Entity\Embeddable\Address", columnPrefix="address")
     */
    private $address;

    /**
     * @var bool True if the voter wants to receive a voting kit
     * @ORM\Column(type="boolean")
     */
    private $voting_kit_requested = true;

    /**
     * @var string The secret string used to verify the identity of the voter later
     * @ORM\Column(type="string")
     */
    private $secret = "";

    /**
     * @var string The token used to confirm the application via email
     * @ORM\Column(type="string")
     */
    private $confirmation_token = "";

    /**
     * @var \DateTime The datetime where the application was confirmed via email
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $confirmation_date = null;

    /**
     * @var bool True if the ballot paper was printed yet.
     * @ORM\Column(type="boolean")
     */
    private $printed = false;

    /**
     * @var bool True if the ballot paper was received and counted
     * @ORM\Column(type="boolean")
     */
    private $counted = false;

    public function __construct()
    {
        $this->address = new Address();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param  string  $email
     * @return PostalVotingRegistration
     */
    public function setEmail(string $email): PostalVotingRegistration
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * @param  string  $first_name
     * @return PostalVotingRegistration
     */
    public function setFirstName(string $first_name): PostalVotingRegistration
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }

    /**
     * @param  string  $last_name
     * @return PostalVotingRegistration
     */
    public function setLastName(string $last_name): PostalVotingRegistration
    {
        $this->last_name = $last_name;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return string
     */
    public function getStudentNumber(): string
    {
        return $this->student_number;
    }

    /**
     * @param  string  $student_number
     * @return PostalVotingRegistration
     */
    public function setStudentNumber(string $student_number): PostalVotingRegistration
    {
        $this->student_number = $student_number;
        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param  Address  $address
     * @return PostalVotingRegistration
     */
    public function setAddress(Address $address): PostalVotingRegistration
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVotingKitRequested(): bool
    {
        return $this->voting_kit_requested;
    }

    /**
     * @param  bool  $voting_kit_requested
     * @return PostalVotingRegistration
     */
    public function setVotingKitRequested(bool $voting_kit_requested): PostalVotingRegistration
    {
        $this->voting_kit_requested = $voting_kit_requested;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param  string  $secret
     * @return PostalVotingRegistration
     */
    public function setSecret(string $secret): PostalVotingRegistration
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfirmationToken(): string
    {
        return $this->confirmation_token;
    }

    /**
     * @param  string  $confirmation_token
     * @return PostalVotingRegistration
     */
    public function setConfirmationToken(string $confirmation_token): PostalVotingRegistration
    {
        $this->confirmation_token = $confirmation_token;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getConfirmationDate(): ?\DateTime
    {
        return $this->confirmation_date;
    }

    /**
     * @param  \DateTime  $confirmation_date
     * @return PostalVotingRegistration
     */
    public function setConfirmationDate(?\DateTime $confirmation_date): PostalVotingRegistration
    {
        $this->confirmation_date = $confirmation_date;
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmation_date !== null;
    }

    /**
     * @return bool
     */
    public function isPrinted(): bool
    {
        return $this->printed;
    }

    /**
     * @param  bool  $printed
     * @return PostalVotingRegistration
     */
    public function setPrinted(bool $printed): PostalVotingRegistration
    {
        $this->printed = $printed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCounted(): bool
    {
        return $this->counted;
    }

    /**
     * @param  bool  $counted
     * @return PostalVotingRegistration
     */
    public function setCounted(bool $counted): PostalVotingRegistration
    {
        $this->counted = $counted;
        return $this;
    }


}