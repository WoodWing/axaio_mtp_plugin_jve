/**
 * User.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class User  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private java.lang.String fullName;

    private java.lang.Boolean deactivated;

    private java.lang.String password;

    private java.lang.Boolean fixedPassword;

    private java.lang.String emailAddress;

    private java.lang.Boolean emailUser;

    private java.lang.Boolean emailGroup;

    private java.lang.Integer passwordExpired;

    private com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty validFrom;

    private com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty validTill;

    private java.lang.String language;

    private java.lang.String trackChangesColor;

    private java.lang.String organization;

    private java.lang.String location;

    private java.lang.String encryptedPassword;

    private com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups;

    private java.lang.Boolean importOnLogon;

    public User() {
    }

    public User(
           java.math.BigInteger id,
           java.lang.String name,
           java.lang.String fullName,
           java.lang.Boolean deactivated,
           java.lang.String password,
           java.lang.Boolean fixedPassword,
           java.lang.String emailAddress,
           java.lang.Boolean emailUser,
           java.lang.Boolean emailGroup,
           java.lang.Integer passwordExpired,
           com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty validFrom,
           com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty validTill,
           java.lang.String language,
           java.lang.String trackChangesColor,
           java.lang.String organization,
           java.lang.String location,
           java.lang.String encryptedPassword,
           com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups,
           java.lang.Boolean importOnLogon) {
           this.id = id;
           this.name = name;
           this.fullName = fullName;
           this.deactivated = deactivated;
           this.password = password;
           this.fixedPassword = fixedPassword;
           this.emailAddress = emailAddress;
           this.emailUser = emailUser;
           this.emailGroup = emailGroup;
           this.passwordExpired = passwordExpired;
           this.validFrom = validFrom;
           this.validTill = validTill;
           this.language = language;
           this.trackChangesColor = trackChangesColor;
           this.organization = organization;
           this.location = location;
           this.encryptedPassword = encryptedPassword;
           this.userGroups = userGroups;
           this.importOnLogon = importOnLogon;
    }


    /**
     * Gets the id value for this User.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this User.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this User.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this User.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the fullName value for this User.
     * 
     * @return fullName
     */
    public java.lang.String getFullName() {
        return fullName;
    }


    /**
     * Sets the fullName value for this User.
     * 
     * @param fullName
     */
    public void setFullName(java.lang.String fullName) {
        this.fullName = fullName;
    }


    /**
     * Gets the deactivated value for this User.
     * 
     * @return deactivated
     */
    public java.lang.Boolean getDeactivated() {
        return deactivated;
    }


    /**
     * Sets the deactivated value for this User.
     * 
     * @param deactivated
     */
    public void setDeactivated(java.lang.Boolean deactivated) {
        this.deactivated = deactivated;
    }


    /**
     * Gets the password value for this User.
     * 
     * @return password
     */
    public java.lang.String getPassword() {
        return password;
    }


    /**
     * Sets the password value for this User.
     * 
     * @param password
     */
    public void setPassword(java.lang.String password) {
        this.password = password;
    }


    /**
     * Gets the fixedPassword value for this User.
     * 
     * @return fixedPassword
     */
    public java.lang.Boolean getFixedPassword() {
        return fixedPassword;
    }


    /**
     * Sets the fixedPassword value for this User.
     * 
     * @param fixedPassword
     */
    public void setFixedPassword(java.lang.Boolean fixedPassword) {
        this.fixedPassword = fixedPassword;
    }


    /**
     * Gets the emailAddress value for this User.
     * 
     * @return emailAddress
     */
    public java.lang.String getEmailAddress() {
        return emailAddress;
    }


    /**
     * Sets the emailAddress value for this User.
     * 
     * @param emailAddress
     */
    public void setEmailAddress(java.lang.String emailAddress) {
        this.emailAddress = emailAddress;
    }


    /**
     * Gets the emailUser value for this User.
     * 
     * @return emailUser
     */
    public java.lang.Boolean getEmailUser() {
        return emailUser;
    }


    /**
     * Sets the emailUser value for this User.
     * 
     * @param emailUser
     */
    public void setEmailUser(java.lang.Boolean emailUser) {
        this.emailUser = emailUser;
    }


    /**
     * Gets the emailGroup value for this User.
     * 
     * @return emailGroup
     */
    public java.lang.Boolean getEmailGroup() {
        return emailGroup;
    }


    /**
     * Sets the emailGroup value for this User.
     * 
     * @param emailGroup
     */
    public void setEmailGroup(java.lang.Boolean emailGroup) {
        this.emailGroup = emailGroup;
    }


    /**
     * Gets the passwordExpired value for this User.
     * 
     * @return passwordExpired
     */
    public java.lang.Integer getPasswordExpired() {
        return passwordExpired;
    }


    /**
     * Sets the passwordExpired value for this User.
     * 
     * @param passwordExpired
     */
    public void setPasswordExpired(java.lang.Integer passwordExpired) {
        this.passwordExpired = passwordExpired;
    }


    /**
     * Gets the validFrom value for this User.
     * 
     * @return validFrom
     */
    public com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty getValidFrom() {
        return validFrom;
    }


    /**
     * Sets the validFrom value for this User.
     * 
     * @param validFrom
     */
    public void setValidFrom(com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty validFrom) {
        this.validFrom = validFrom;
    }


    /**
     * Gets the validTill value for this User.
     * 
     * @return validTill
     */
    public com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty getValidTill() {
        return validTill;
    }


    /**
     * Sets the validTill value for this User.
     * 
     * @param validTill
     */
    public void setValidTill(com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty validTill) {
        this.validTill = validTill;
    }


    /**
     * Gets the language value for this User.
     * 
     * @return language
     */
    public java.lang.String getLanguage() {
        return language;
    }


    /**
     * Sets the language value for this User.
     * 
     * @param language
     */
    public void setLanguage(java.lang.String language) {
        this.language = language;
    }


    /**
     * Gets the trackChangesColor value for this User.
     * 
     * @return trackChangesColor
     */
    public java.lang.String getTrackChangesColor() {
        return trackChangesColor;
    }


    /**
     * Sets the trackChangesColor value for this User.
     * 
     * @param trackChangesColor
     */
    public void setTrackChangesColor(java.lang.String trackChangesColor) {
        this.trackChangesColor = trackChangesColor;
    }


    /**
     * Gets the organization value for this User.
     * 
     * @return organization
     */
    public java.lang.String getOrganization() {
        return organization;
    }


    /**
     * Sets the organization value for this User.
     * 
     * @param organization
     */
    public void setOrganization(java.lang.String organization) {
        this.organization = organization;
    }


    /**
     * Gets the location value for this User.
     * 
     * @return location
     */
    public java.lang.String getLocation() {
        return location;
    }


    /**
     * Sets the location value for this User.
     * 
     * @param location
     */
    public void setLocation(java.lang.String location) {
        this.location = location;
    }


    /**
     * Gets the encryptedPassword value for this User.
     * 
     * @return encryptedPassword
     */
    public java.lang.String getEncryptedPassword() {
        return encryptedPassword;
    }


    /**
     * Sets the encryptedPassword value for this User.
     * 
     * @param encryptedPassword
     */
    public void setEncryptedPassword(java.lang.String encryptedPassword) {
        this.encryptedPassword = encryptedPassword;
    }


    /**
     * Gets the userGroups value for this User.
     * 
     * @return userGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.UserGroup[] getUserGroups() {
        return userGroups;
    }


    /**
     * Sets the userGroups value for this User.
     * 
     * @param userGroups
     */
    public void setUserGroups(com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups) {
        this.userGroups = userGroups;
    }


    /**
     * Gets the importOnLogon value for this User.
     * 
     * @return importOnLogon
     */
    public java.lang.Boolean getImportOnLogon() {
        return importOnLogon;
    }


    /**
     * Sets the importOnLogon value for this User.
     * 
     * @param importOnLogon
     */
    public void setImportOnLogon(java.lang.Boolean importOnLogon) {
        this.importOnLogon = importOnLogon;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof User)) return false;
        User other = (User) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.fullName==null && other.getFullName()==null) || 
             (this.fullName!=null &&
              this.fullName.equals(other.getFullName()))) &&
            ((this.deactivated==null && other.getDeactivated()==null) || 
             (this.deactivated!=null &&
              this.deactivated.equals(other.getDeactivated()))) &&
            ((this.password==null && other.getPassword()==null) || 
             (this.password!=null &&
              this.password.equals(other.getPassword()))) &&
            ((this.fixedPassword==null && other.getFixedPassword()==null) || 
             (this.fixedPassword!=null &&
              this.fixedPassword.equals(other.getFixedPassword()))) &&
            ((this.emailAddress==null && other.getEmailAddress()==null) || 
             (this.emailAddress!=null &&
              this.emailAddress.equals(other.getEmailAddress()))) &&
            ((this.emailUser==null && other.getEmailUser()==null) || 
             (this.emailUser!=null &&
              this.emailUser.equals(other.getEmailUser()))) &&
            ((this.emailGroup==null && other.getEmailGroup()==null) || 
             (this.emailGroup!=null &&
              this.emailGroup.equals(other.getEmailGroup()))) &&
            ((this.passwordExpired==null && other.getPasswordExpired()==null) || 
             (this.passwordExpired!=null &&
              this.passwordExpired.equals(other.getPasswordExpired()))) &&
            ((this.validFrom==null && other.getValidFrom()==null) || 
             (this.validFrom!=null &&
              this.validFrom.equals(other.getValidFrom()))) &&
            ((this.validTill==null && other.getValidTill()==null) || 
             (this.validTill!=null &&
              this.validTill.equals(other.getValidTill()))) &&
            ((this.language==null && other.getLanguage()==null) || 
             (this.language!=null &&
              this.language.equals(other.getLanguage()))) &&
            ((this.trackChangesColor==null && other.getTrackChangesColor()==null) || 
             (this.trackChangesColor!=null &&
              this.trackChangesColor.equals(other.getTrackChangesColor()))) &&
            ((this.organization==null && other.getOrganization()==null) || 
             (this.organization!=null &&
              this.organization.equals(other.getOrganization()))) &&
            ((this.location==null && other.getLocation()==null) || 
             (this.location!=null &&
              this.location.equals(other.getLocation()))) &&
            ((this.encryptedPassword==null && other.getEncryptedPassword()==null) || 
             (this.encryptedPassword!=null &&
              this.encryptedPassword.equals(other.getEncryptedPassword()))) &&
            ((this.userGroups==null && other.getUserGroups()==null) || 
             (this.userGroups!=null &&
              java.util.Arrays.equals(this.userGroups, other.getUserGroups()))) &&
            ((this.importOnLogon==null && other.getImportOnLogon()==null) || 
             (this.importOnLogon!=null &&
              this.importOnLogon.equals(other.getImportOnLogon())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getFullName() != null) {
            _hashCode += getFullName().hashCode();
        }
        if (getDeactivated() != null) {
            _hashCode += getDeactivated().hashCode();
        }
        if (getPassword() != null) {
            _hashCode += getPassword().hashCode();
        }
        if (getFixedPassword() != null) {
            _hashCode += getFixedPassword().hashCode();
        }
        if (getEmailAddress() != null) {
            _hashCode += getEmailAddress().hashCode();
        }
        if (getEmailUser() != null) {
            _hashCode += getEmailUser().hashCode();
        }
        if (getEmailGroup() != null) {
            _hashCode += getEmailGroup().hashCode();
        }
        if (getPasswordExpired() != null) {
            _hashCode += getPasswordExpired().hashCode();
        }
        if (getValidFrom() != null) {
            _hashCode += getValidFrom().hashCode();
        }
        if (getValidTill() != null) {
            _hashCode += getValidTill().hashCode();
        }
        if (getLanguage() != null) {
            _hashCode += getLanguage().hashCode();
        }
        if (getTrackChangesColor() != null) {
            _hashCode += getTrackChangesColor().hashCode();
        }
        if (getOrganization() != null) {
            _hashCode += getOrganization().hashCode();
        }
        if (getLocation() != null) {
            _hashCode += getLocation().hashCode();
        }
        if (getEncryptedPassword() != null) {
            _hashCode += getEncryptedPassword().hashCode();
        }
        if (getUserGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getUserGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getUserGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getImportOnLogon() != null) {
            _hashCode += getImportOnLogon().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(User.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "User"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fullName");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FullName"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deactivated");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Deactivated"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("password");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Password"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fixedPassword");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FixedPassword"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("emailAddress");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EmailAddress"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("emailUser");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EmailUser"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("emailGroup");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EmailGroup"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("passwordExpired");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PasswordExpired"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("validFrom");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ValidFrom"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("validTill");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ValidTill"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("language");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Language"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("trackChangesColor");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TrackChangesColor"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("organization");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Organization"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("location");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Location"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("encryptedPassword");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EncryptedPassword"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "UserGroup"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UserGroup"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("importOnLogon");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ImportOnLogon"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
