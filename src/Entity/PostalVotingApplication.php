<?php


namespace App\Entity;


use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Entity\Embeddable\Address;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"email"}, message="validator.email_already_used")
 */
class PostalVotingApplication implements DBElementInterface, TimestampedElementInterface
{
    use TimestampTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string The email address of the student, where the verification will be send.
     * @ORM\Column(type="string", unique=true)
     * @Assert\Regex("/^.*@uni-jena.de$/", message="validator.must_be_uni_jena_address")
     * @Assert\Email(mode="strict")
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
     * @Assert\Regex("/^\d[1-6]$/", message="validator.invalid_student_number")
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

    public function getId(): ?int
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
     * @return PostalVotingApplication
     */
    public function setEmail(string $email): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setFirstName(string $first_name): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setLastName(string $last_name): PostalVotingApplication
    {
        $this->last_name = $last_name;
        return $this;
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
     * @return PostalVotingApplication
     */
    public function setStudentNumber(string $student_number): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setAddress(Address $address): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setVotingKitRequested(bool $voting_kit_requested): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setSecret(string $secret): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setConfirmationToken(string $confirmation_token): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setConfirmationDate(?\DateTime $confirmation_date): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setPrinted(bool $printed): PostalVotingApplication
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
     * @return PostalVotingApplication
     */
    public function setCounted(bool $counted): PostalVotingApplication
    {
        $this->counted = $counted;
        return $this;
    }


}