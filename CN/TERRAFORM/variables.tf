variable "aws_region" { 

  description = "Región de AWS" 
  type        = string 
} 

variable "my_lab" {
  description = "Mi laboratorio"
  type = string
}

variable "vpc_cidr" { 

  description = "CIDR de la VPC" 
  type        = string 
} 

variable "public_subnet_cidr" { 

  description = "CIDR de la subred pública" 
  type        = string 
} 

variable "private_subnet_cidr" { 

  description = "CIDR de la subred privada" 
  type        = string 
}

variable "private_subnet_cidr_2" { 

  description = "CIDR de la segunda subred privada" 
  type        = string 
}

variable "availability_zone" { 

  description = "Zona de disponibilidad" 
  type        = string 
} 

variable "availability_zone_2" { 

  description = "Segunda zona de disponibilidad" 
  type        = string 
}

variable "ami_id" { 

  description = "AMI para las instancias EC2" 
  type        = string 
} 

variable "instance_type" { 

  description = "Tipo de instancia EC2" 
  type        = string 
} 

variable "key_name" { 

  description = "Nombre del key pair" 
  type        = string 
} 