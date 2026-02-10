
# VPC 

resource "aws_vpc" "main_vpc" { 

  cidr_block           = var.vpc_cidr 
  enable_dns_support   = true 
  enable_dns_hostnames = true 

  tags = { 
    Name = "main-vpc" 
  } 
} 

# Internet Gateway 

resource "aws_internet_gateway" "igw" { 

  vpc_id = aws_vpc.main_vpc.id 

  
  tags = { 
    Name = "main-igw" 
  } 
} 

# Subred Pública 

resource "aws_subnet" "public_subnet" { 

  vpc_id                  = aws_vpc.main_vpc.id 
  cidr_block              = var.public_subnet_cidr 
  availability_zone       = var.availability_zone 
  map_public_ip_on_launch = true 

  tags = {
    Name = "public-subnet" 
  } 
} 

# Subred Privada 

resource "aws_subnet" "private_subnet" { 

  vpc_id            = aws_vpc.main_vpc.id 
  cidr_block        = var.private_subnet_cidr 
  availability_zone = var.availability_zone 

  tags = { 
    Name = "private-subnet" 
  } 
} 

# Segunda Subred Privada

resource "aws_subnet" "private_subnet_2" { 

  vpc_id            = aws_vpc.main_vpc.id 
  cidr_block        = var.private_subnet_cidr_2 
  availability_zone = var.availability_zone_2

  tags = { 
    Name = "segprivate-subnet" 
  } 
} 

# Tabla de rutas pública 

resource "aws_route_table" "public_rt" { 

  vpc_id = aws_vpc.main_vpc.id 
  route { 
    cidr_block = "0.0.0.0/0" 
    gateway_id = aws_internet_gateway.igw.id 
  } 
  tags = { 
    Name = "public-rt" 
  } 
} 
resource "aws_route_table_association" "public_assoc" { 

  subnet_id      = aws_subnet.public_subnet.id 
  route_table_id = aws_route_table.public_rt.id 
} 

# Tabla de rutas privada 

resource "aws_route_table" "private_rt" { 

  vpc_id = aws_vpc.main_vpc.id 
  route { 
    cidr_block = "0.0.0.0/0" 
    network_interface_id = aws_instance.nat_instance.primary_network_interface_id 
  } 
  tags = { 
    Name = "private-rt" 
  } 
} 
resource "aws_route_table_association" "private_assoc" { 
  subnet_id      = aws_subnet.private_subnet.id 
  route_table_id = aws_route_table.private_rt.id 
} 

# Security Group Proxy 

resource "aws_security_group" "proxy_sg" { 
    
  name   = "proxy-sg" 
  vpc_id = aws_vpc.main_vpc.id 
  ingress { 
    from_port   = 22 
    to_port     = 22 
    protocol    = "tcp" 
    cidr_blocks = ["0.0.0.0/0"] 
  } 
  egress { 
    from_port   = 0 
    to_port     = 0 
    protocol    = "-1" 
    cidr_blocks = ["0.0.0.0/0"] 
  } 
} 

# Security Group NAT 

resource "aws_security_group" "nat_sg" { 

  name   = "nat-sg" 
  vpc_id = aws_vpc.main_vpc.id 
  ingress { 
    from_port   = 0 
    to_port     = 0 
    protocol    = "-1" 
    cidr_blocks = [var.private_subnet_cidr] 
  }
  ingress { 
    from_port   = 0 
    to_port     = 0 
    protocol    = "-1" 
    cidr_blocks = [var.private_subnet_cidr_2] 
  }
  ingress { 
    from_port   = 22 
    to_port     = 22 
    protocol    = "tcp" 
    cidr_blocks = ["0.0.0.0/0"] 
  } 
  egress { 
    from_port   = 0 
    to_port     = 0 
    protocol    = "-1" 
    cidr_blocks = ["0.0.0.0/0"] 
  } 
} 

# Security Group web

resource "aws_security_group" "web_sg" { 

  name   = "web-sg" 
  vpc_id = aws_vpc.main_vpc.id 
  ingress { 
    from_port = 0
    to_port   = 0
    protocol  = "-1"
    cidr_blocks = [var.public_subnet_cidr] 
  }
  ingress { 
    from_port   = 22 
    to_port     = 22 
    protocol    = "tcp" 
    cidr_blocks = [var.private_subnet_cidr] 
  } 
  egress { 
    from_port   = 0 
    to_port     = 0 
    protocol    = "-1" 
    cidr_blocks = ["0.0.0.0/0"] 
  } 
}

# Security Group monitorización
resource "aws_security_group" "monitorizacion_sg" { 

  name   = "monitorizacion-sg" 
  vpc_id = aws_vpc.main_vpc.id 
  ingress { 
    from_port   = 22 
    to_port     = 22 
    protocol    = "tcp" 
    cidr_blocks = [var.private_subnet_cidr, var.private_subnet_cidr_2] 
  } 
  egress { 
    from_port   = 0 
    to_port     = 0 
    protocol    = "-1" 
    cidr_blocks = [var.private_subnet_cidr, var.private_subnet_cidr_2] 
  }
}

# Instancia Proxy 

resource "aws_instance" "proxy" { 

  ami           = var.ami_id 
  instance_type = var.instance_type 
  subnet_id     = aws_subnet.public_subnet.id 
  key_name      = var.key_name 
  vpc_security_group_ids = [aws_security_group.proxy_sg.id] 
  tags = { 
    Name = "proxy"
  } 
} 

# Instancia NAT 

resource "aws_instance" "nat_instance" { 

  ami                    = var.ami_id 
  instance_type          = var.instance_type 
  subnet_id              = aws_subnet.public_subnet.id 
  key_name               = var.key_name
  source_dest_check      = false 
  vpc_security_group_ids = [aws_security_group.nat_sg.id] 
  tags = { 
    Name = "nat-instance" 
  } 
} 

# Instancia WEB1  

resource "aws_instance" "servidor_web1" {  

  ami                    = var.ami_id  
  instance_type          = var.instance_type  
  subnet_id              = aws_subnet.private_subnet.id
  key_name               = var.key_name
  vpc_security_group_ids = [aws_security_group.web_sg.id]
  tags = { 
    Name = "web1" 
  }
}

# Instancia WEB2 

resource "aws_instance" "servidor_web2" {  
  ami                    = var.ami_id  
  instance_type          = var.instance_type  
  subnet_id              = aws_subnet.private_subnet_2.id
  key_name               = var.key_name
  vpc_security_group_ids = [aws_security_group.web_sg.id]
  tags = { 
    Name = "web2" 
  }
}

# Instancia Monitorización

resource "aws_instance" "servidor_monitorizacion" {  
  ami                    = var.ami_id  
  instance_type          = var.instance_type  
  subnet_id              = aws_subnet.private_subnet.id
  key_name               = var.key_name
  vpc_security_group_ids = [aws_security_group.monitorizacion_sg.id]
  tags = { 
    Name = "monitorizacion" 
  }
}